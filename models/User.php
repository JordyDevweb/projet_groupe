<?php

class User {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 🔍 Trouver par email
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    // 🔍 Trouver par ID
    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ➕ Créer un utilisateur
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO users 
            (nom, email, password, promotion, filiere, metier, photo, bio)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['nom'],
            $data['email'],
            $data['password'], // ⚠️ déjà hashé
            $data['promotion'],
            $data['filiere'],
            $data['metier'],
            $data['photo'],
            $data['bio']
        ]);

        return $this->pdo->lastInsertId();
    }

    // ✏️ Mettre à jour profil
    public function updateProfile($id, $data) {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET promotion = ?,
                filiere = ?,
                metier = ?,
                bio = ?,
                photo = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['promotion'],
            $data['filiere'],
            $data['metier'],
            $data['bio'],
            $data['photo'],
            $id
        ]);
    }

    // 🔎 Recherche annuaire
    public function search($query = '', $promotion = null) {

        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];

        if (!empty($query)) {
            $sql .= " AND (nom LIKE ? OR email LIKE ? OR filiere LIKE ? OR metier LIKE ?)";
            $like = "%$query%";
            array_push($params, $like, $like, $like, $like);
        }

        if (!empty($promotion)) {
            $sql .= " AND promotion = ?";
            $params[] = $promotion;
        }

        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    // 🔐 Reset password
    public function setResetToken($id, $token, $expire) {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET reset_token = ?, reset_token_expire = ?
            WHERE id = ?
        ");
        $stmt->execute([$token, $expire, $id]);
    }

    public function findByResetToken($token) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users
            WHERE reset_token = ?
            AND reset_token_expire > NOW()
        ");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function updatePassword($id, $hashedPassword) {
        $stmt = $this->pdo->prepare("
            UPDATE users
            SET password = ?, reset_token = NULL, reset_token_expire = NULL
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $id]);
    }

}