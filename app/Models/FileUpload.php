<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;


class FileUpload extends Model
{
    public static function uploadToAzureBlobStorage($file)
    {
        $connectionString = 'Your_connectionString'; //your connection string here.

        $containerName = 'Your_containerName';//Your container name here.

        try {
            $blobRestProxy = BlobRestProxy::createBlobService($connectionString);

            // Check if the container exists, and create it if not
            $containers = $blobRestProxy->listContainers();
            $containerExists = false;

            foreach ($containers->getContainers() as $container) {
                if ($container->getName() === $containerName) {
                    $containerExists = true;
                    break;
                }
            }

            if (!$containerExists) {
                $blobRestProxy->createContainer($containerName);
                \Log::info('Container created: ' . $containerName);
            } else {
                \Log::info('Container already exists: ' . $containerName);
            }

            $blobName = uniqid() . '-' . $file->getClientOriginalName();

            \Log::info('Blob Name: ' . $blobName);

            $blobRestProxy->createBlockBlob($containerName, $blobName, fopen($file->getRealPath(), 'r'));

            // Construct the URL for the uploaded file without the container name
            $url = sprintf('%s/%s', env('AZURE_STORAGE_URL'), $blobName);

            // Save the file information to the database using Eloquent
            $uploadedFile = new FileUpload();
            $uploadedFile->filename = $blobName;
            $uploadedFile->url = $url;
            $uploadedFile->save();

            \Log::info('File uploaded successfully. URL: ' . $url);

            return $url; // Return the URL immediately after upload
        } catch (ServiceException $e) {
            \Log::error("Azure Storage Exception: " . $e->getMessage());
            return null;
        }
    }

    public static function deleteFromAzureBlobStorage($filename)
    {
        $connectionString = 'DefaultEndpointsProtocol=https;AccountName=testdatafiles;AccountKey=PQAl2b/n5+7tE+QomCei+CoObFhKAixB/Y1dBQdtDa2KmjHeQT/7g+PkEAc9rlD+ds2JqOaRBfPl+ASt1xaH+A==;EndpointSuffix=core.windows.net';

        $containerName = 'fortestdata';

        try {
            $blobRestProxy = BlobRestProxy::createBlobService($connectionString);

            // List blobs in the container
            $blobs = $blobRestProxy->listBlobs($containerName);

            foreach ($blobs->getBlobs() as $blob) {
                if ($blob->getName() === $filename) {
                    // Delete the blob from Azure Blob Storage
                    $blobRestProxy->deleteBlob($containerName, $filename);
                    \Log::info('Blob deleted successfully: ' . $filename);

                    // Delete the corresponding database record
                    $fileRecord = FileUpload::where('filename', $filename)->first();

                    if ($fileRecord) {
                        $fileRecord->delete();
                        \Log::info('Database record deleted successfully: ' . $filename);
                    } else {
                        \Log::info('Database record does not exist: ' . $filename);
                    }

                    return true; // Return true if deletion is successful
                }
            }

            \Log::info('Blob does not exist: ' . $filename);
            return false; // Return false if the blob does not exist
        } catch (ServiceException $e) {
            \Log::error("Azure Storage Exception: " . $e->getMessage());
            return false; // Return false if an exception occurs
        }
    }

}



// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
// use MicrosoftAzure\Storage\Blob\BlobRestProxy;
// use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

// class FileUpload extends Model
// {
//     public static function uploadToAzureBlobStorage($file)
//     {
//         $connectionString = 'DefaultEndpointsProtocol=https;AccountName=testdatafiles;AccountKey=PQAl2b/n5+7tE+QomCei+CoObFhKAixB/Y1dBQdtDa2KmjHeQT/7g+PkEAc9rlD+ds2JqOaRBfPl+ASt1xaH+A==;EndpointSuffix=core.windows.net';

//         $containerName = 'fortestdata';

//         try {
//             $blobRestProxy = BlobRestProxy::createBlobService($connectionString);

//             // Check if the container exists, and create it if not
//             $containers = $blobRestProxy->listContainers();
//             $containerExists = false;

//             foreach ($containers->getContainers() as $container) {
//                 if ($container->getName() === $containerName) {
//                     $containerExists = true;
//                     break;
//                 }
//             }

//             if (!$containerExists) {
//                 $blobRestProxy->createContainer($containerName);
//                 \Log::info('Container created: ' . $containerName);
//             } else {
//                 \Log::info('Container already exists: ' . $containerName);
//             }

//             $blobName = uniqid() . '-' . $file->getClientOriginalName();

//             // Log blob name
//             \Log::info('Blob Name: ' . $blobName);

//             $blobRestProxy->createBlockBlob($containerName, $blobName, fopen($file->getRealPath(), 'r'));

//             // Generate a shared access signature (SAS) token with read permission
//             $sasToken = $blobRestProxy->createBlobSharedAccessSignature(
//                 $containerName,
//                 $blobName,
//                 'r',
//                 new \DateTime('2030-01-01')
//             );

//             // Construct the URL for the publicly accessible blob using the SAS token
//             $url = sprintf('%s/%s/%s?%s', env('AZURE_STORAGE_URL'), $containerName, $blobName, $sasToken);

//             // Save the file information to the database using Eloquent
//             $uploadedFile = new FileUpload();
//             $uploadedFile->filename = $blobName;
//             $uploadedFile->url = $url;
//             $uploadedFile->save();

//             \Log::info('File uploaded successfully. URL: ' . $url);

//             return $url; // Return the URL immediately after upload
//         } catch (ServiceException $e) {
//             \Log::error("Azure Storage Exception: " . $e->getMessage());
//             return null;
//         }
//     }
// }
