<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $table = 'rooms';
    protected $fillable = [
        'room_number', 'type', 'price', 'capacity', 
        'description', 'amenities', 'status', 'image'
    ];
    
    protected $casts = [
        'price' => 'float',
        'capacity' => 'integer'
    ];
    
    public $timestamps = true;
    
    public function bookings()
    {
        return $this->hasMany(RoomBooking::class);
    }
    
    public function getAmenitiesArrayAttribute()
    {
        return explode(',', $this->amenities);
    }
}