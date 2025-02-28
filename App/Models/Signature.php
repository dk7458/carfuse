namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use App\Services\EncryptionService;

class Signature extends BaseModel
{
    protected $table = 'signatures';
    protected $resourceName = 'signature';
    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    /**
     * Create a new signature.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        if (isset($data['signature'])) {
            $data['signature'] = EncryptionService::encrypt($data['signature']);
        }

        return parent::create($data);
    }

    /**
     * Update a signature.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['signature'])) {
            $data['signature'] = EncryptionService::encrypt($data['signature']);
        }

        return parent::update($id, $data);
    }

    /**
     * Get the signature.
     *
     * @param int $signatureId
     * @return string|null
     */
    public function getSignature(int $signatureId): ?string
    {
        $signature = $this->find($signatureId);

        if ($signature && isset($signature['signature'])) {
            return EncryptionService::decrypt($signature['signature']);
        }

        return null;
    }

    /**
     * Get the user associated with the signature.
     *
     * @param int $signatureId
     * @return array|null
     */
    public function getUser(int $signatureId): ?array
    {
        $signature = $this->find($signatureId);
        
        if (!$signature || !isset($signature['user_id'])) {
            return null;
        }
        
        $query = "SELECT * FROM users WHERE id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $signature['user_id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
