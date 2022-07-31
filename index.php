<?php include("tete-combat.php") ?>

<?php



class Personnage 
        { 
            private $_id,
                     $_degats, 
                     $_nom; 
                     
                     const CEST_MOI = 1;
// Constante renvoyée par la méthode ` frapper` si on se frappe soi-même.
                     const PERSONNAGE_TUE = 2;
     // Constante renvoyée par la mé thode `frapper` si on a tué le personnage en le frappant. 
                      const PERSONNAGE_FRAPPE = 3;
// Constante renvoyée par la mé thode `frapper` si on a bien frappé le personnage.

public function nomValide()
{
    return !empty($this->_nom);
} 

public function __construct(array $donnees)
  {
       $this->hydrate($donnees);
    } 

     public function frapper(Personnage $perso)
    { 
    if ($perso->id() == $this->_id)
    {
     return self::CEST_MOI;
    } 
    // On indique au personnage qu'il doit recevoir des dégâts. 
    // Puis on retourne la valeur renvoyée par la méthode : self::PERSONNAGE_TUE ou self::PERSONNAGE_FRAPPE 
    return $perso->recevoirDegats(); 
    } 

    public function hydrate(array $donnees)
     {
          foreach ($donnees as $key => $value)
          {
              $method = 'set'.ucfirst($key);
              if (method_exists($this, $method))
               {
                    $this->$method($value);
            }
        }
     } 
    
     public function recevoirDegats() 
     { 
        $this->_degats += 5; 
     // Si on a 100 de dégâts ou plus, on dit que le personnage a été tué. 
        if ($this->_degats >= 100)
        {
             return self::PERSONNAGE_TUE;
         }
    // Sinon, on se contente de dire que le personnage a bien é té frappé. 
         return self::PERSONNAGE_FRAPPE;
     }

      // GETTERS // 

     public function degats()
     {
          return $this->_degats;
         } 

         public function id() 
         { 
             return $this->_id; 
            }
            
        public function nom()
         { 
             return $this->_nom;
            
        } 

        public function setDegats($degats)
         { 
             $degats = (int) $degats;
             if ($degats >= 0 && $degats <= 100)
             { 
                  $this->_degats = $degats;
             }
         } 
    

 public function setId($id)
 {
     $id = (int) $id;
      if ($id > 0)
       {
           $this->_id = $id;
     }
 } 

 public function setNom($nom)
 {
      if (is_string($nom)) 
      { 
           $this->_nom = $nom;
       }
 } 
}


class PersonnagesManager
{ 
    private $_db;
public function __construct($db)
    {
        $this->setDb($db);
    }
    
public function add(Personnage $perso)
 {
    $q = $this->_db->prepare('INSERT INTO personnages SET nom = :nom');
    $q->bindValue(':nom', $perso->nom());
    $q->execute();
    $perso->hydrate(array(
        'id' => $this->_db->lastInsertId(),
        'degats' => 0,
    )); 
}
public function count()
 {
    return $this->_db->query('SELECT COUNT(*) FROM personnages' )->fetchColumn(); 
}

public function delete(Personnage $perso)
 { 
    $this->_db->exec('DELETE FROM personnages WHERE id = '. $perso->id());

 }
 
 public function exists($info)
 { 
    if (is_int($info)) // On veut voir si tel personnage ayant pour id $info existe.
    {
        return (bool) $this->_db->query('SELECT COUNT(*) FROM personnages WHERE id = '.$info)->fetchColumn();
     }
    // Sinon, c'est qu'on veut vérifier que le nom existe ou pas.

    $q = $this->_db->prepare('SELECT COUNT(*) FROM personnages WHERE nom = :nom');
     $q->execute(array(':nom' => $info));
     
     return (bool) $q->fetchColumn(); 
 }


public function get($info)
{ 
    if (is_int($info))
     {
          $q = $this->_db->query('SELECT id, nom, degats FROM personnages WHERE id = '.$info);
          $donnees = $q->fetch(PDO::FETCH_ASSOC);
          
          return new Personnage($donnees);
        }
         else
          {
               $q = $this->_db->prepare('SELECT id, nom, degats FROM personnages WHERE nom = :nom');
               $q->execute(array(':nom' => $info));
               return new Personnage($q->fetch(PDO::FETCH_ASSOC));
            } 
}
public function getList($nom)
{
     $persos = array();
     $q = $this->_db->prepare('SELECT id, nom, degats FROM personnages WHERE nom <> :nom ORDER BY nom');
     $q->execute(array(':nom' => $nom));
     while ($donnees = $q->fetch(PDO::FETCH_ASSOC))
     {
         $persos[] = new Personnage($donnees);
        }
        return $persos; 

}

public function update(Personnage $perso)
{ 
    $q = $this->_db->prepare('UPDATE personnages SET degats = :degats
    WHERE id = :id');
    $q->bindValue(':degats', $perso->degats(), PDO::PARAM_INT);
    $q->bindValue(':id', $perso->id(), PDO::PARAM_INT);
    
    $q->execute();
     
}

public function setDb(PDO $db)
{
    $this->_db = $db;
}

}
    

?>
    <header>
        <p><span>Nombre de personnages créés : </span><?php echo $manager->count (); ?>
</p>
    </header>
<?php
 if (isset($message)) // On a un message à afficher ?
 echo '<p>', $message, '</p>'; // Si oui, on l'affiche.
  
  if (isset($perso)) // Si on utilise un personnage (nouveau ou pas).
  {
      ?>
   <p class="deco"><a href="?deconnexion=1" >  <input type="bouton" class="dec" value="Déconnexion"> </a></p>
    <fieldset class="me"> <legend><strong><?php echo htmlspecialchars($perso->nom()); ?></strong> </legend>
    
    <img src="perso.jpg" alt="moi" />
     <p class="moi"><?php echo $perso->degats(); ?> Dégâts !</em></p>
    </p> </fieldset>
    
    <fieldset> <legend>Qui frapper ?</legend> <p>
     <?php
     $persos = $manager->getList($perso->nom());
     if (empty($persos))
     {
         echo 'Personne à frapper !';
          }
    else
    {
         foreach ($persos as $unPerso)
echo  '<span class="noms">', htmlspecialchars($unPerso->nom()), '</span>
<a class = "feux" href="?frapper=', $unPerso->id(), '">
<img src="persos.jpg" alt="', htmlspecialchars($unPerso->nom()),'" />
    <span class="degat">', $unPerso->degats(), ' Dégâts</span></a><br />';
     }
      ?>
     </p> </fieldset>
     <?php
    }
    else
    {
         ?> 

    <form action="" method="post">
    
    <p><input type="text" name="nom" placeholder="Nom du personnage"/> </p>
   <p> <input class="bouton" type="submit" value="Créer ce personnage" name=" creer" /></p>
   <p> <input class="bouton" type="submit" value="Utiliser ce personnage" name="utiliser" /></p>
    
    </form> 
    
    <?php
    }
    ?>

        </body>
        </html>

        <?php 
        if (isset($perso))
    // Si on a créé un personnage, on le stocke dans une variable session afin d'économiser une requête SQL.
    {
        $_SESSION['perso'] = $perso;
    }
