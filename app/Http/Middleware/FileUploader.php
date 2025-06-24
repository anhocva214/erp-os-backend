<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Symfony\Component\HttpFoundation\Response;

class FileUploader
{
    protected function generateFileName($bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    // Store files upload folder in disk
    protected function getDestinationPath(): string
    {
        return 'uploads';
    }

    public function handle(Request $request, Closure $next, $maxFileCount = 1): Response
    {
        try {
            $filesArray = [];
            $routeName = $request->route()->getName();
            $request->validate([
                'images.*' => 'required|file|mimes:jpg,jpeg,png,pdf,webp',
            ]);

            if ($request->hasFile('images')) {
                if ($routeName == 'sliderImages.create' || $routeName == 'sliderImages.update') {
                    if (count($request->file('images')) > $maxFileCount) {
                        throw new Exception("You can upload a maximum of $maxFileCount files.");
                    }

                    foreach ($request->file('images') as $file) {
                        $extension = $file->getClientOriginalExtension();
                        $uniqueSuffix = $this->generateFileName();
                        $filename = $uniqueSuffix . '.' . $extension;
                        $file->storeAs($this->getDestinationPath(), $filename);
                        $filesArray[] = $filename;
                    }
                } else {
                    if (count($request->file('images')) > $maxFileCount) {
                        throw new Exception("You can upload a maximum of $maxFileCount files.");
                    }
                    //if image size is greater than 2MB then show error message
                    foreach ($request->file('images') as $file) {
                        if ($file->getSize() > 2097152) {
                            throw new Exception("You can upload a maximum of 2MB file.");
                        }
                    }
                    foreach ($request->file('images') as $file) {
                        $extension = $file->getClientOriginalExtension();
                        $manager = new ImageManager(new Driver());
                        $uniqueSuffix = $this->generateFileName();
                        $filename = $uniqueSuffix . '.' . $extension;

                        $image = $manager->read($file);
                        $encode = $image->toWebp(50);
                        $directory = base_path('storage/app/' . $this->getDestinationPath());
                       
                        if (!file_exists($directory)) {
                            mkdir($directory, 0777, true);
                        }
                        $encode->save($directory . '/' . $filename);

                        $filesArray[] = $filename;
                    }
                }
            }else if($request->hasFile('files')){
            
                if (count($request->file('files')) > $maxFileCount) {
                    throw new Exception("You can upload a maximum of $maxFileCount files.");
                }
               foreach ($request->file('files') as $file) {
                    $extension = $file->getClientOriginalExtension();
                    $uniqueSuffix = $this->generateFileName();
                    $filename = $uniqueSuffix . '.' . $extension;
                    $file->storeAs($this->getDestinationPath(), $filename);
                    $filesArray[] = $filename;
                }
            }

            $request->merge(['file_paths' => $filesArray]);

            return $next($request);
        } catch (Exception $error) {
            return response()->json(["message" => $error->getMessage()]);
        }
    }
}
