<!-- ---------------------------------------------------------
* Page d'accueil du plugin dans l'admin 
* + Formulaire de création d'un quiz
* ------------------------------------------------------------ 
-->
<?php
// Tableau des erreurs
$aErrors = ["empty_ko" => "Tous les champs sont obligatoires.",
            "cre_ok" => "Le quiz a été ajoutée.",
            "cre_ko" => "L'ajout du quiz a échoué.",
            "del_ok" => "Le quiz a été supprimée.",
            "tech_ko" => "Une erreur technique ou de sécurité est survenue."           
];
?>
<div class="wrap">   
    <h2>Mon Quiz</h2>
</div>
<!-- Menu à onglets de l'admin du plugin -->
<div id="menuquiz">
    <ul>
	   <!-- l'onglet 'Créer un quiz' prend par défaut la classe CSS 'active' (fond noir) -->
       <li id="active">Créer un quiz</li>
	   <?php
	   // Liste des quiz
       $quizList = $this->quiz_getQuizList();
     
       // Un quiz = un onglet dans le menu
       if ($quizList)
       {
          foreach ($quizList as $q)
          {
             $href = "?page=my-osm/my-osm.php&map=".$q->id;
	         echo "<li><a href='".$href."'>".$q->titre."</a></li>\n";
          }
       }
       ?>
</ul>
</div><!--fin #menuquiz-->
<div id="contentquiz">

    <?php     
    // Affichage des erreurs + vérif. qu'elles existent bien dans le tableau
    if (isset($_GET["msg"]) && array_key_exists($_GET["msg"], $aErrors)) 
    { 
        // si finit par 'ok' = texte vert, si par 'ko' = texte rouge    
        // substr(chaine, -2) extrait les 2 derniers caractères, ici soit 'ok' soit 'ko'
        $color = substr($_GET["msg"], -2);
        echo"<div class='msg-".$color."'>".$aErrors[$_GET["msg"]]."</div>\n";   
    }    
    ?>        

    <h3 class="title" >Créez un Quiz :</h3>
    <form action="?page=my-quiz/my-quiz.php&action=createquiz" method="post">
        <p id="Mg-title-error" style="color:red;display:none;">Entrez un titre, svp</p> 
        <p><label for="Mg-title">Titre* :</label><br><input type="text" id="Mg-title" name="Mg-title"></p>
            
        <p id="Mg-question-error" style="color:red;display:none;">Entrez une question, svp</p>
        <p><label for="Mg-question">Question* :</label><br><input type="text" id="Mg-question" name="Mg-question"></p>
            
        <p id="Mg-reponse-error" style="color:red;display:none;">Entrez une reponse, svp</p>
        <p><label for="Mg-reponse">Réponse* :</label><br><input type="text" id="Mg-reponse" name="Mg-reponse"></p>

        <p id="Mg-resultat-error" style="color:red;display:none;">Entrez un résultat, svp</p>
        <p><label for="Mg-resultat">Résultat* :</label><br><input type="text" id="Mg-resultat" name="Mg-resultat"></p>
              
        <p><input type="button" class="button button-primary" id="bt-quiz" value="Enregistrer"></p>
        <small>* champs obligatoires</small>    
    </form>
    
    <div>
    <p><strong>Exemples :</strong></p>
    <ul>
        <li>Trafic Organique: Pas Satisfait|Moyennement Satisfait|Très Satisfait</li>
        <li>Trafic Réseaux: Pas Satisfait|Moyennement Satisfait|Très Satisfait</li>
        <li>Trafic Emailing: Pas Satisfait|Moyennement Satisfait|Très Satisfait</li>
    </ul>
    </div>
    
</div><!--fin #contentmap-->