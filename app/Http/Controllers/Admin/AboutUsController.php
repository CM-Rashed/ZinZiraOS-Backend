<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\AboutUs;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AboutUsController extends Controller
{
    // Fetch or create default About Us settings instance
    public function index()
    {
        $settings = AboutUs::firstOrCreate([]);
        return response()->json($settings);
    }

    // Update settings with partial field and dynamic file upload validation support
    public function update(Request $request)
    {
        $settings = AboutUs::firstOrCreate([]);

        // Base validation rules for text/data fields
        $rules = [
            'slider_hero_text'           => 'nullable|string',
            'slider_headline'            => 'nullable|string',
            'slider_paragraph'           => 'nullable|string',
            'slider_button'              => 'nullable|string',

            'why_choose_hero_text'       => 'nullable|string',
            'why_choose_headline'        => 'nullable|string',
            'why_choose_paragraph'       => 'nullable|string',

            'card1_header'               => 'nullable|string',
            'card1_title'                => 'nullable|string',

            'card2_header'               => 'nullable|string',
            'card2_title'                => 'nullable|string',

            'card3_header'               => 'nullable|string',
            'card3_title'                => 'nullable|string',

            'physical_outlets_header'    => 'nullable|string',
            'physical_outlets_title'     => 'nullable|string',

            'stores_location'           => 'nullable|string',
            'stores_google_map_location' => 'nullable|string',
            'working_hours'              => 'nullable|string',
            'hotline_number_1'           => 'nullable|string',
            'hotline_number_2'           => 'nullable|string',
            'hotmail'                    => 'nullable|email',

            'our_vision_header'          => 'nullable|string',
            'our_vision_paragraph'       => 'nullable|string',
        ];

        // Apply strict file validation ONLY when an actual binary file is sent
        $imageFields = ['card1_icon', 'card2_icon', 'card3_icon', 'our_vision_image'];
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $rules[$field] = 'nullable|file|image|mimes:jpeg,png,jpg,svg,webp|max:5120';
            } else {
                $rules[$field] = 'nullable';
            }
        }

        $request->validate($rules);

        // Extract only the fields sent in the request payload (skipping image key strings)
        $data = $request->only(array_diff(array_keys($request->all()), $imageFields));

        $uploadPath = public_path('uploads/about_us_assets');

        // Ensure upload directory exists
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Process uploaded binary images
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                // Remove old image directly from public directory if present
                if (!empty($settings->$field)) {
                    $oldFilePath = public_path($settings->$field);
                    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                // Process and move new image file
                $image = $request->file($field);
                $imageName = time() . '_' . $field . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);
                
                $data[$field] = 'uploads/about_us_assets/' . $imageName;
            }
        }

        // Apply partial field updates
        $settings->update($data);

        return response()->json([
            'message' => 'About Us settings updated successfully.',
            'data'    => $settings
        ]);
    }
}