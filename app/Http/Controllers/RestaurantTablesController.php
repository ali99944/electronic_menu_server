<?php

namespace App\Http\Controllers;

use App\Events\FreeUpTableEvent;
use App\Models\Restaurants; // Import Restaurants model
use App\Models\RestaurantTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // For file storage
use Illuminate\Validation\Rule; // For unique validation scoped to restaurant
use chillerlan\QRCode\QRCode; // QR Code generator
use chillerlan\QRCode\QROptions;
use Exception; // To catch potential errors

class RestaurantTablesController extends Controller
{
    /**
     * Display all tables (consider filtering by restaurant in a real app).
     */
    public function index(Request $request)
    {
        // $portal = request()->user();
        $restaurant_tables = RestaurantTables::all();

        return response()->json([
            'data' => $restaurant_tables
        ]);
    }

    /**
     * Store a new table, generate QR code with centered number overlay + text below,
     * save to public path, and link to the authenticated user's restaurant.
     */
    public function store(Request $request)
    {
        if (!auth()->check() || !auth()->user()->restaurants_id) {
            return response()->json(['message' => 'Unauthorized or restaurant not associated.'], 403);
        }
        $restaurantId = auth()->user()->restaurants_id;

        $validation = Validator::make($request->all(), [
            'table_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('restaurant_tables')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurants_id', $restaurantId); // Correct column name if different
                }),
            ],
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validation->errors()
            ], 422);
        }

        // --- Get Data ---
        $tableNumber = $request->input('table_number');

        // --- Configuration ---
        // Use public_path for fonts expected to be in the public/fonts directory
        $overlayFontPath = public_path('fonts/alfont_com_Alilato-ExtraLight.ttf');  // <<<--- UPDATE FILENAME
        $bottomFontPath = public_path('fonts/alfont_com_Alilato-ExtraLight.ttf'); // <<<--- UPDATE FILENAME

        if (!file_exists($overlayFontPath) || !file_exists($bottomFontPath)) {
            logger()->error("Font file missing.", ['overlay' => $overlayFontPath, 'bottom' => $bottomFontPath]);
            return response()->json(['message' => 'Server configuration error: Font file missing.'], 500);
        }

        $overlayFontSize = 256; // Adjusted for better fit, 128 was likely too big for overlay
        $qrCodeColorHex = '#A70000';
        $bottomText = "ORBIS Q";
        $bottomFontSize = 64; // Using 64 instead of 128 which might be excessively large
        $bottomTextPaddingTop = 30; // Space between QR and bottom text
        $bottomTextPaddingBottom = 30; // Space below bottom text


        // --- QR Code Generation & Image Manipulation ---
        $finalImageResource = null; // Initialize resource variable
        $qrImageResource = null;

        try {
            // 1. Define QR Code Data
            $qrData = 'https://emenu.sourcemediaagency.com/restaurants/' . $restaurantId . '/emenu?table_number=' . $tableNumber;

            // 2. Configure QR Options
            $options = new QROptions([
                'outputType'    => QRCode::OUTPUT_IMAGE_PNG,
                'imageBase64'   => false,
                'eccLevel'      => QRCode::ECC_H, // High ECC needed for overlay reliability
                'scale'         => 15, // Slightly larger scale might help overlay clarity
                'quietZoneSize' => 1, // Minimal margin
                'imageTransparent' => false,
                'backgroundColor' => '#ffffff', // White background for QR itself
            ]);

            // 3. Generate Base QR Code
            $qrCodeImageData = (new QRCode($options))->render($qrData);
            $qrImageResource = @imagecreatefromstring($qrCodeImageData);
            // if (!$qrImageResource instanceof GdImage) { // Use instanceof GdImage
            //     throw new Exception("Failed to create image from QR code data.");
            // }
            $qrWidth = imagesx($qrImageResource);
            $qrHeight = imagesy($qrImageResource);

            // 4. Overlay Table Number (Improved Centering)
            $numberText = (string)$tableNumber;
            list($r, $g, $b) = sscanf($qrCodeColorHex, "#%02x%02x%02x") ?? [null, null, null];
            if ($r === null) throw new Exception("Invalid hex color format.");
            // Allocate colors *on the $qrImageResource*
            $overlayTextColor = imagecolorallocate($qrImageResource, $r, $g, $b);
            $overlayPatchColor = imagecolorallocatealpha($qrImageResource, 255, 255, 255, 70); // Semi-transparent white bg

            // Calculate text box for overlay number
            $overlayBbox = imagettfbbox($overlayFontSize, 0, $overlayFontPath, $numberText);
            if ($overlayBbox === false) throw new Exception("Failed calculation: overlay text bbox.");
            $overlayTextWidth = $overlayBbox[2] - $overlayBbox[0];
            $overlayTextHeight = $overlayBbox[1] - $overlayBbox[7]; // Height based on ascender/descender

            // --- Improved Centering for Overlay ---
            // Center X for text bounding box
            $overlayTextX = ($qrWidth - $overlayTextWidth) / 2;
            // Center Y for text baseline: QR middle + half text "visual" height
            // Adjust Y based on distance from baseline to absolute bottom ($overlayBbox[7]) - usually negative
            $overlayTextY = ($qrHeight / 2) + ($overlayTextHeight / 2);


            // Patch dimensions - ensure it covers text height visually
            $patchPadding = 15;
            $patchWidth = $overlayTextWidth + (2 * $patchPadding);
            $patchHeight = $overlayTextHeight + (2 * $patchPadding);

            // Patch top-left coordinates - centered
            $patchX1 = ($qrWidth - $patchWidth) / 2;
            $patchY1 = ($qrHeight - $patchHeight) / 2;
            $patchX2 = $patchX1 + $patchWidth;
            $patchY2 = $patchY1 + $patchHeight;

            // Ensure patch stays within image bounds
            $patchX1 = max(0, (int)$patchX1);
            $patchY1 = max(0, (int)$patchY1);
            $patchX2 = min($qrWidth, (int)$patchX2);
            $patchY2 = min($qrHeight, (int)$patchY2);

            // Draw patch FIRST
            imagefilledrectangle($qrImageResource, $patchX1, $patchY1, $patchX2, $patchY2, $overlayPatchColor);
            // Draw text SECOND (on top of patch)
            imagettftext($qrImageResource, $overlayFontSize, 0, (int)$overlayTextX, (int)$overlayTextY, $overlayTextColor, $overlayFontPath, $numberText);
            // --- End Overlay ---


            // 5. Prepare for Bottom Text ("ORBIS Q")
            $bottomTextColorAlloc = [176, 0, 0]; // Black for bottom text
            $bottomBbox = imagettfbbox($bottomFontSize, 0, $bottomFontPath, $bottomText);
             if ($bottomBbox === false) throw new Exception("Failed calculation: bottom text bbox.");
            $bottomTextWidth = $bottomBbox[2] - $bottomBbox[0];
            $bottomTextHeight = $bottomBbox[1] - $bottomBbox[7]; // Height using ascender/descender

            // 6. Create Final Canvas (Taller)
            $finalHeight = $qrHeight + $bottomTextPaddingTop + $bottomTextHeight + $bottomTextPaddingBottom;
            $finalImageResource = imagecreatetruecolor($qrWidth, (int)$finalHeight);
            // if (!$finalImageResource instanceof GdImage) {
            //     throw new Exception("Failed to create final image canvas.");
            // }

            // Fill final canvas with white background
            $finalBgColor = imagecolorallocate($finalImageResource, 255, 255, 255);
            imagefill($finalImageResource, 0, 0, $finalBgColor);

            // Allocate color for bottom text *on the final image*
            $bottomTextColor = imagecolorallocate($finalImageResource, ...$bottomTextColorAlloc);


            // 7. Copy Modified QR Code to Top of Final Canvas
            imagecopy($finalImageResource, $qrImageResource, 0, 0, 0, 0, $qrWidth, $qrHeight);

            // 8. Calculate and Draw Bottom Text ("ORBIS Q")
            $bottomTextX = ($qrWidth - $bottomTextWidth) / 2;
            // Position baseline Y correctly below the QR part
            $bottomTextY = $qrHeight + $bottomTextPaddingTop + $bottomTextHeight - abs($bottomBbox[7]); // Y is baseline
            imagettftext($finalImageResource, $bottomFontSize, 0, (int)$bottomTextX, (int)$bottomTextY, $bottomTextColor, $bottomFontPath, $bottomText);

            // 9. Define Save Path (Public Directory)
            $filename = 'table_' . $tableNumber . '_qr_' . time() . '.png';
            // Relative path within the public disk root
            $publicRelativePath = "qrcodes/restaurants/{$restaurantId}/tables/{$filename}";
            // Full server path to the public directory for saving
            $fullSavePath = public_path($publicRelativePath);
            // Get directory part for checking/creation
            $directoryPath = dirname($fullSavePath);

            // 10. Create Directory if needed (using File facade for convenience)
            if (!File::isDirectory($directoryPath)) {
                if (!File::makeDirectory($directoryPath, 0775, true, true)) { // Recursive, mode
                    throw new Exception("Failed to create directory: " . $directoryPath);
                }
            }

            // 11. Save the final image
            if (!imagepng($finalImageResource, $fullSavePath)) {
                throw new Exception("Failed to save final QR code image to: " . $fullSavePath);
            }

             // Path to store in the DB is the *relative* public path
             $dbPath = $publicRelativePath;

        } catch (Exception $e) {
            logger()->error("QR Code Generation Failed: " . $e->getMessage(), ['exception' => $e]);
            // Clean up temporary resources if they were created
            //  if ($qrImageResource instanceof GdImage) imagedestroy($qrImageResource);
            //  if ($finalImageResource instanceof GdImage) imagedestroy($finalImageResource);

            return response()->json([
                'message' => 'Failed to generate or save QR code.',
                'error' => $e->getMessage() // Provide error details cautiously in production
            ], 500);
        } finally {
             // Ensure resources are always freed if they exist
            //  if ($qrImageResource instanceof GdImage) imagedestroy($qrImageResource);
            //  if ($finalImageResource instanceof GdImage) imagedestroy($finalImageResource);
        }

        // --- Create Database Record ---
        try {
            $table = RestaurantTables::create([
                'table_number' => $tableNumber,
                'restaurants_id' => $restaurantId, // Use the variable holding the ID
                'qrcode' => $dbPath, // Store relative public path
            ]);
        } catch (Exception $e) {
            logger()->error("Failed to create table record in DB: " . $e->getMessage(), ['exception' => $e]);
             // Optionally delete the generated QR file if DB save fails
             if (isset($fullSavePath) && File::exists($fullSavePath)) {
                 File::delete($fullSavePath);
             }
            return response()->json(['message' => 'Failed to save table data.'], 500);
        }


        // Add the full URL accessor to the response data if defined in model
        if (method_exists($table, 'getQrcodeUrlAttribute')) {
             $table->append('qrcode_url');
        }


        return response()->json([
            'message' => 'Table created successfully with custom QR code.',
            'data' => $table
        ], 201);
    }
    /**
     * Delete a table (consider deleting the QR code file too).
     */
    public function destroy(Request $request, $id)
    {
        $table = RestaurantTables::where('id', $id)->first();

        if (!$table) {
            return response()->json(['message' => 'Table not found'], 404);
        }

        // Delete the associated QR code file from storage
        // if ($table->qrcode && Storage::disk('public')->exists($table->qrcode)) {
        //     Storage::disk('public')->delete($table->qrcode);
        // }

        $table->delete();

        return response()->json([
            'message' => 'Table and associated QR code deleted successfully.',
            'status' => true
        ]);
    }

    /**
     * Update table status.
     */
    public function updateStatus(Request $request, $id)
    {
        // Validate the status input
        $validation = Validator::make($request->all(), [
            'status' => 'required|in:reserved,busy,free'
        ]);

        if ($validation->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validation->errors()
            ], 422);
        }

        $table = RestaurantTables::find($id);

        if (!$table) {
            return response()->json([
                'message' => 'Restaurant table not found'
            ], 404);
        }

        // Update the table status
        $table->status = $request->status;
        $table->save();

        return response()->json([
            'message' => 'Table status updated successfully.',
            'data' => $table
        ]);
    }

    public function makeTableFree(Request $request, $id)
    {
        $table = RestaurantTables::find($id);

        if (!$table) {
            return response()->json([
                'message' => 'Table not found'
            ], 404);
        }

        $table->status = 'free';
        $table->save();

        event(new FreeUpTableEvent($table->table_number));

        return response()->json([
            'message' => 'Table status updated successfully.',
            'data' => $table
        ]);
    }


    public function get_restaurant_tables()
    {
        $restaurantId = request()->user()->restaurants_id;
        $tables = RestaurantTables::where('restaurants_id', $restaurantId)->get();

        return response()->json([
            'data' => $tables
        ]);
    }
}
