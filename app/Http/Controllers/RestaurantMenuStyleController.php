<?php

namespace App\Http\Controllers;

use App\Models\RestaurantMenuStyle;
use Illuminate\Http\Request;

class RestaurantMenuStyleController extends Controller
{
    public function styles($id)
    {
        $styles = RestaurantMenuStyle::where('restaurants_id', $id)->first();

        return response()->json([
            'data' => $styles
        ]);
    }

    public function css_styles($id)
    {
        $styles = RestaurantMenuStyle::where('restaurants_id', $id)->with('font')->first();


        $css = "
            @font-face {
                font-family: 'alilato';
                src: url('http://localhost:8000/{$styles->font->link}') format('truetype'); /* Path to the font file */
                font-weight: normal;
                font-style: normal;
            }
            :root {
                --color-primary: {$styles->primary_color};
                --color-primary-text: {$styles->primary_text_color};

                --color-primary-banner-title: {$styles->banner_title_color};
                --color-primary-banner-description: {$styles->banner_description_color};

                --color-primary-header-bg: {$styles->header_bg_color};
                --color-primary-header-text: {$styles->header_text_color};

                --color-menu-background: {$styles->menu_background_color};

                font-family: 'alilato', sans-serif;
            }
        ";

        return response($css, 200, [
            'Content-Type' => 'text/css',
        ]);
    }

    public function update_styles(Request $request, $id)
    {
        $restaurant_styles = RestaurantMenuStyle::where('restaurants_id', $id)->first();

        $restaurant_styles->update([
            'is_portal_active' => $request->is_portal_active,
            'is_restaurant_active' => $request->is_restaurant_active,
            'is_meals_activated' => $request->is_meals_activated,
        ]);

        return response()->json([
            'message' => 'styles updated successfully'
        ]);

    }
}
