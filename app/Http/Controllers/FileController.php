<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FileUpload;


class FileController extends Controller
{
    public function uploadFile(Request $request)
    {
        $file = $request->file('file');

        // Ensure a file is uploaded
        $url = FileUpload::uploadToAzureBlobStorage($file);

        if ($url) {
            // File uploaded successfully, use $url as needed
            return response()->json(['message' => 'File uploaded successfully', 'url' => $url]);
        } else {
            // Failed to upload file
            return response()->json(['message' => 'Failed to upload file'], 500);
        }
    }
    public function delete($filename)
    {
        $result = FileUpload::deleteFromAzureBlobStorage($filename);

        if ($result) {
            return response()->json(['message' => 'File deleted successfully'], 200);
        } else {
            return response()->json(['message' => 'Failed to delete file'], 500);
        }
    }
}
