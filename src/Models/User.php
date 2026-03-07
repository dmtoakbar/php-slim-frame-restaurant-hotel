<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['username', 'password', 'email', 'full_name', 'role'];
    protected $hidden = ['password'];
    
    public $timestamps = true;
    
    public function roomBookings()
    {
        return $this->hasMany(RoomBooking::class);
    }
    
    public function restaurantReservations()
    {
        return $this->hasMany(RestaurantReservation::class);
    }
}