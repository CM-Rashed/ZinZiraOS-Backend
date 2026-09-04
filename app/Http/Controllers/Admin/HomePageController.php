<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\HomePage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomePageController extends Controller
{
    // Fetch or create default setting instance
    public function index()
    {
        $settings = HomePage::firstOrCreate([]);
        return response()->json($settings);
    }

    // Update settings with partial field and flexible image upload support
    public function update(Request $request)
    {
        $settings = HomePage::firstOrCreate([]);

        // Validation rules use 'sometimes' on images so string paths don't fail validation
        $rules = [
            'website_name'      => 'nullable|string',
            'tagline'           => 'nullable|string',
            'shop_location'     => 'nullable|string',
            'slider1_hero_text' => 'nullable|string',
            'slider1_headline'  => 'nullable|string',
            'slider1_paragraph' => 'nullable|string',
            'slider1_button'    => 'nullable|string',
            'slider2_hero_text' => 'nullable|string',
            'slider2_headline'  => 'nullable|string',
            'slider2_paragraph' => 'nullable|string',
            'slider2_button'    => 'nullable|string',
            'card1_headline'    => 'nullable|string',
            'card1_text'        => 'nullable|string',
            'card2_headline'    => 'nullable|string',
            'card2_text'        => 'nullable|string',
            'card3_headline'    => 'nullable|string',
            'card3_text'        => 'nullable|string',
            'card4_headline'    => 'nullable|string',
            'card4_text'        => 'nullable|string',
        ];

        // Apply strict file validation ONLY if an actual file binary was uploaded
        $imageFields = ['website_logo', 'slider1_image', 'slider2_image'];
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $rules[$field] = 'nullable|file|image|mimes:jpeg,png,jpg,svg,webp|max:5120';
            } else {
                $rules[$field] = 'nullable';
            }
        }

        $request->validate($rules);

        // Gather only text fields present in the current request payload
        $data = $request->only(array_diff(array_keys($request->all()), $imageFields));

        $uploadPath = public_path('uploads/shop_assets');

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        // Process uploaded image files
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                if (!empty($settings->$field)) {
                    $oldFilePath = public_path($settings->$field);
                    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $image = $request->file($field);
                $imageName = time() . '_' . $field . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);
                
                $data[$field] = 'uploads/shop_assets/' . $imageName;
            }
        }

        // Apply single or partial field updates
        $settings->update($data);

        return response()->json([
            'message' => 'Shop settings updated successfully.',
            'data'    => $settings
        ]);
    }
}