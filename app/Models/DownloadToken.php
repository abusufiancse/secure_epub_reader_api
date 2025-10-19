<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadToken extends Model {
    protected $fillable = ['user_id','ebook_id','token','expires_at'];
    protected $casts = ['expires_at'=>'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function ebook(): BelongsTo { return $this->belongsTo(Ebook::class); }
}
