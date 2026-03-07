<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $table = 'menu_items';
    protected $fillable = [
        'category_id', 'name', 'description', 
        'price', 'image', 'is_available', 'is_featured', 'dietary_info'
    ];
    
    protected $casts = [
        'price' => 'float',
        'is_available' => 'boolean',
        'is_featured' => 'boolean'
    ];
    
    public $timestamps = true;
    
    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }
}