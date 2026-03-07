<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomBooking extends Model
{
    protected $table = 'room_bookings';
    protected $fillable = [
        'room_id', 'user_id', 'guest_name', 'guest_email', 'guest_phone',
        'check_in', 'check_out', 'adults', 'children', 'total_price',
        'status', 'payment_status', 'special_requests'
    ];
    
    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'total_price' => 'float'
    ];
    
    public $timestamps = true;
    
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}