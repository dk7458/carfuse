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
    private EncryptionService $encryptionService;

    public function __construct(DatabaseHelper $db, EncryptionService $encryptionService = null)
    {
        parent::__construct($db);
        // If encryption service is not injected, try to resolve it through container or create new instance
        $this->encryptionService = $encryptionService ?? app(EncryptionService::class) ?? new EncryptionService();
    }

    /**
     * Create a new signature.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        if (isset($data['signature'])) {
            $data['signature'] = $this->encryptionService->encrypt($data['signature']);
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
            $data['signature'] = $this->encryptionService->encrypt($data['signature']);
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
            return $this->encryptionService->decrypt($signature['signature']);
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

    /**
     * Store signature file path.
     *
     * @param int $userId
     * @param string $filePath
     * @param bool $encrypted
     * @return int
     */
    public function storeSignaturePath(int $userId, string $filePath, bool $encrypted = true): int
    {
        return $this->create([
            'user_id' => $userId,
            'file_path' => $filePath,
            'encrypted' => $encrypted,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get signatures by user ID.
     *
     * @param int $userId
     * @return array
     */
    public function getSignaturesByUserId(int $userId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get signature path by user ID.
     *
     * @param int $userId
     * @return string|null
     */
    public function getSignaturePathByUserId(int $userId): ?string
    {
        $query = "SELECT file_path FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        return $result ? $result['file_path'] : null;
    }
}
