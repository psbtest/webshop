<?php
declare(strict_types=1);

namespace App\Models;

class AdminUser extends BaseModel
{
    protected string $table = 'admin_users';

    /**
     * @return array<string, mixed>|null
     */
    public function find_by_username(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE username = ?");
        $stmt->execute([$username]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find_by_email(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function verify_password(string $username, string $password): bool
    {
        $user = $this->find_by_username($username);
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create_user(array $data): int
    {
        $user_data = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
        ];
        
        return $this->create($user_data);
    }

    public function update_password(int $id, string $new_password): bool
    {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed_password, $id]);
    }

    public function username_exists(string $username, ?int $exclude_id = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = ?";
        $params = [$username];
        
        if ($exclude_id !== null) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function email_exists(string $email, ?int $exclude_id = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($exclude_id !== null) {
            $sql .= " AND id != ?";
            $params[] = $exclude_id;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
