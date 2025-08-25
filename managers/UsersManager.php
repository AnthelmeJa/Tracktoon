<?php

class UsersManager extends AbstractManager
{
    public function __construct() {
        parent::__construct();
    }

    public function findByMail(string $mail): ?Users
    {
        $query = $this->db->prepare('SELECT * FROM users WHERE mail = :mail');
        $query->execute(['mail' => $mail]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $user = new Users(
                $result['pseudo'],
                $result['mail'],
                $result['password'],
                $result['role'],
                true
            );
            $user->setId((int) $result['id']);
            return $user;
        }

        return null;
    }

    public function create(Users $user): ?Users
    {
        $query = $this->db->prepare('INSERT INTO users (pseudo, mail, password, role) VALUES (:pseudo, :mail, :password, :role)' );
                
        $result = $query->execute([
            'pseudo'   => $user->getPseudo(),
            'mail'     => $user->getMail(),
            'password' => $user->getPassword(),
            'role'     => $user->getRole()
        ]);

        if ($result) {
            $id = (int) $this->db->lastInsertId();
            $user->setId($id);
            return $user;
        }

        return null;
    }

    public function update(Users $user): bool
    {
        $query = $this->db->prepare('UPDATE users SET pseudo = :pseudo, mail = :mail, role = :role, password = :password WHERE id = :id' );
             
        $parameters = [
            'id'       => $user->getId(),
            'pseudo'   => $user->getPseudo(),
            'mail'     => $user->getMail(),
            'role'     => $user->getRole(),
            'password' => $user->getPassword()
        ];

        return $query->execute($parameters);
    }
        
        
    public function delete(Users $user){
            $query = $this->db->prepare('DELETE FROM users WHERE id = :id;');
            
            $parameters=[
                'id'=>$user->getId()

            ];
            
            $query->execute($parameters);
        }
    
}