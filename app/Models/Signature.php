namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\EncryptionService;

class Signature extends BaseModel
{
    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Ensure secure storage handling using encryption
    public function setSignatureAttribute($value)
    {
        $this->attributes['signature'] = EncryptionService::encrypt($value);
    }

    public function getSignatureAttribute($value)
    {
        return EncryptionService::decrypt($value);
    }

    // Implement validation rules to only allow specific file types
    public static function rules()
    {
        return [
            'signature' => 'required|mimes:png,jpg,svg|max:2048',
        ];
    }
}
