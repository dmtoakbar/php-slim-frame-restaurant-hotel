<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $table = 'menu_categories';
    protected $fillable = ['name', 'description', 'sort_order'];
    
    public $timestamps = true;
    
    public function items()
    {
        return $this->hasMany(MenuItem::class, 'category_id');
    }
}