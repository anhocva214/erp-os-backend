#!/bin/bash

# Định nghĩa các biến
IMAGE_NAME="erp-api"           # Tên của Docker image
TAG="latest"                  # Tag của image
CONTAINER_NAME="erp-api"  # Tên của container
PORT="3800:80"              # Port mapping (host:container)
HOST_PORT=$(echo ${PORT} | cut -d':' -f1)  # Lấy port host
CONTAINER_PORT=$(echo ${PORT} | cut -d':' -f2)  # Lấy port container
RANDOM_PORT=$((1024 + RANDOM % 64511)) # Tạo port ngẫu nhiên trong phạm vi hợp lệ (1024-65535)
TEMP_PORT="${RANDOM_PORT}:${CONTAINER_PORT}"  # Port tạm thời ngẫu nhiên
DOCKERFILE_PATH="./Dockerfile"           # Đường dẫn tới Dockerfile
NETWORK_NAME="apps"           # Tên network
HEALTH_CHECK_TIMEOUT=30       # Thời gian chờ health check (giây)

# Màu sắc cho output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Hàm kiểm tra lỗi
check_error() {
    if [ $? -ne 0 ]; then
        echo -e "${RED}❌ Lỗi: $1${NC}"
        exit 1
    fi
}

# Hàm kiểm tra health check
check_health() {
    local container_name=$1
    local start_time=$(date +%s)
    local end_time=$((start_time + HEALTH_CHECK_TIMEOUT))
    
    echo -e "${YELLOW}🔍 Đang kiểm tra container ${container_name}...${NC}"
    
    while [ $(date +%s) -lt $end_time ]; do
        if docker ps -q -f name=${container_name} | grep -q .; then
            echo -e "${GREEN}✅ Container ${container_name} đang chạy${NC}"
            return 0
        fi
        sleep 2
    done
    
    echo -e "${RED}❌ Container ${container_name} không chạy${NC}"
    return 1
}

# Kiểm tra Docker có được cài đặt không
if ! command -v docker &> /dev/null; then
    echo -e "${RED}🚫 Docker chưa được cài đặt. Vui lòng cài đặt Docker trước!${NC}"
    exit 1
fi

echo -e "${YELLOW}🚀 === Bắt đầu build và chạy Docker container ===${NC}"

# Kiểm tra xem Dockerfile có tồn tại không
if [ ! -f "${DOCKERFILE_PATH}" ]; then
    echo -e "${RED}📂 Không tìm thấy Dockerfile tại ${DOCKERFILE_PATH}${NC}"
    exit 1
fi

# Kiểm tra và tạo network nếu chưa tồn tại
if ! docker network ls | grep -q "${NETWORK_NAME}"; then
    echo -e "${GREEN}🌐 Tạo network: ${NETWORK_NAME}...${NC}"
    docker network create ${NETWORK_NAME}
    check_error "Tạo network thất bại"
else
    echo -e "${YELLOW}🌐 Network ${NETWORK_NAME} đã tồn tại${NC}"
fi

# Kiểm tra xem image đã tồn tại chưa
if docker images -q ${IMAGE_NAME}:${TAG} &> /dev/null; then
    echo -e "${YELLOW}🖼️ Image ${IMAGE_NAME}:${TAG} đã tồn tại. Đang xóa để build bản mới...${NC}"
    docker rmi ${IMAGE_NAME}:${TAG} 2>/dev/null || true
fi

# Build Docker image mới
echo -e "${GREEN}🔨 Đang build Docker image: ${IMAGE_NAME}:${TAG}...${NC}"
docker build -t ${IMAGE_NAME}:${TAG} -f ${DOCKERFILE_PATH} .
check_error "Build image thất bại"

# Kiểm tra xem container cũ có tồn tại không
if [ "$(docker ps -a -q -f name=${CONTAINER_NAME})" ]; then
    echo -e "${YELLOW}🔄 Bắt đầu quy trình rolling update...${NC}"
    
    # Chạy container mới với port tạm thời
    echo -e "${GREEN}🚢 Đang chạy container mới: ${CONTAINER_NAME}_new...${NC}"
    docker run -d --name ${CONTAINER_NAME}_new \
        --network ${NETWORK_NAME} \
        -p ${TEMP_PORT} \
        ${IMAGE_NAME}:${TAG}
    check_error "Chạy container mới thất bại"
    
    # Kiểm tra health check container mới
    if check_health "${CONTAINER_NAME}_new"; then
        echo -e "${GREEN}✅ Container mới hoạt động tốt. Đang chuyển đổi...${NC}"
        
        # Dừng và xóa container cũ
        echo -e "${YELLOW}🛑 Đang dừng container cũ: ${CONTAINER_NAME}...${NC}"
        docker stop ${CONTAINER_NAME} 2>/dev/null || true
        docker rm ${CONTAINER_NAME} 2>/dev/null || true
        
        # Dừng container tạm thời
        echo -e "${YELLOW}🛑 Đang dừng container tạm thời...${NC}"
        docker stop ${CONTAINER_NAME}_new 2>/dev/null || true
        docker rm ${CONTAINER_NAME}_new 2>/dev/null || true
        
        # Chạy container mới với port chính thức
        echo -e "${GREEN}🚢 Đang chạy container mới với port chính thức...${NC}"
        docker run -d --name ${CONTAINER_NAME} \
            --network ${NETWORK_NAME} \
            --restart always \
            -p ${PORT} \
            ${IMAGE_NAME}:${TAG}
        check_error "Chạy container mới thất bại"
        
        echo -e "${GREEN}✅ Rolling update thành công!${NC}"
    else
        echo -e "${RED}❌ Container mới không hoạt động tốt. Đang rollback...${NC}"
        docker stop ${CONTAINER_NAME}_new 2>/dev/null || true
        docker rm ${CONTAINER_NAME}_new 2>/dev/null || true
        exit 1
    fi
else
    # Nếu không có container cũ, chạy container mới với port mapping
    echo -e "${GREEN}🚢 Đang chạy container mới: ${CONTAINER_NAME}...${NC}"
    docker run -d --name ${CONTAINER_NAME} \
        --network ${NETWORK_NAME} \
        --restart always \
        -p ${PORT} ${IMAGE_NAME}:${TAG}
    check_error "Chạy container thất bại"
fi

# Kiểm tra trạng thái container
echo -e "${YELLOW}🔍 Kiểm tra trạng thái container...${NC}"
sleep 2
if [ "$(docker ps -q -f name=${CONTAINER_NAME})" ]; then
    echo -e "${GREEN}✅ Container ${CONTAINER_NAME} đang chạy với phiên bản mới!${NC}"
    # Lấy port host thực tế từ chuỗi PORT
    HOST_PORT=$(echo ${PORT} | cut -d':' -f1)
    echo -e "${GREEN}🌐 Truy cập ứng dụng tại: http://localhost:${HOST_PORT}${NC}"
else
    echo -e "${RED}❗ Container không chạy. Kiểm tra log để biết thêm chi tiết:${NC}"
    docker logs ${CONTAINER_NAME}
    exit 1
fi