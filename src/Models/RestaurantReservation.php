<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantReservation extends Model
{
    protected $table = 'restaurant_reservations';
    protected $fillable = [
        'user_id', 'guest_name', 'guest_email', 'guest_phone',
        'reservation_date', 'reservation_time', 'guests',
        'special_requests', 'status'
    ];
    
    protected $casts = [
        'reservation_date' => 'date',
        'guests' => 'integer'
    ];
    
    public $timestamps = true;
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}