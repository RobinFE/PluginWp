<?php 
/** -------------------------------------------------------
*  Détail d'une carte dans l'admin (onglet 'nom du quiz)
* + Formulaires de modification et de suppression d'un quiz
* ----------------------------------------------------------
*/
// Carte courante
if (isset($_GET['quiz']))
{    
    // On s'assure que l'id est numérique
    // cf. https://stackoverflow.com/questions/236406/is-there-a-difference-between-is-int-and-ctype-digit
    if (is_numeric($_GET['quiz']))
    {
        $map = $this->quiz_getQuiz($_GET['quiz']);
    } 
    else 
    {
        $bDisplay = FALSE;     
    }    
} 
else 
{     
    $bDisplay = FALSE;     
}

// Liste des cartes (pour les onglets de navigation)
$mapList = $this->quiz_getQuizList();

// S'il y a des erreurs, on affiche/redirige sur le template 'admin_home.php'
if ( (isset($bDisplay) && $bDisplay === FALSE) || !isset($quiz) || empty($quiz) || !isset($quizList) || empty($quizList))
{
    $sUrl = $this->url."&msg=tech_ko";   
    echo"<script type='text/javascript'>window.location.replace('".$sUrl."');</script>\n";
    exit;
}

// Tableau des erreurs 
$aErrors = ["empty_ko" => "Tous les champs sont obligatoires.",
            "upd_ok" => "Le quiz a été mise à jour.",
            "upd_ko" => "La modification du quiz a échoué.",
            "del_ko" => "La suppression du quiz a échoué.",
           ];
?>
<div class="wrap">   
    <h2>Mon Quiz</h2>
</div>
<!-- Menu à onglets de l'admin du plugin -->
<div id="menuquiz">
    <ul>
        <li><a href="<?php echo $this->url; ?>">Créer un quiz</a></li>
        <?php  
	    // Un quiz = un onglet dans le menu
        if ($quizList)
        {
            foreach ($quizList as $q)
            {       
                if ($_GET['quiz'] == $q->id)
                { 
                    $active = " id='active'";
		        }
		        else 
		        {
		          $active = "";
		        }
                   
		    $href = $this->url."&quiz=".$q->id;
		    echo "<li ".$active."><a href='".$href."'>".$q->titre."</a></li>\n";
           }
      }
      else 
      {
          echo"<div class='msg-ko'>Une erreur est survenue</div>";
          exit;
      }
      ?>
      </ul>
</div> <!--fin #menumap -->  
<div id="contentquiz2">
    <h2 class="title" >Quiz : <?php echo $quiz->titre; ?></h2>
    <?php 
    // Affichage des erreurs + vérif. qu'elles existent bien dans le tableau
    if (isset($_GET["msg"]) && array_key_exists($_GET["msg"], $aErrors)) 
    { 
        // si finit par 'ok' = fond vert, si finit par 'ko' = fond rouge    
        // substr(chaine, -2) extrait les 2 derniers caractères, ici soit 'ok' soit 'ko'
        $color = substr($_GET["msg"], -2);
        echo"<div class='msg-".$color."'>".$aErrors[$_GET["msg"]]."</div>\n";   
    }    
    ?>        
	<!-- Génération et affichage du shortcode --> 
    <div id="placecode">
        Copiez (ctrl+C) le code et collez (ctrl+V) dans la page ou l'article où vous voulez voir apparaître votre quiz :
        <input id="codequiz" type="text" value="[quiz id=<?php echo $quiz->id ?>]" readonly>
    </div>
       	 
    <div class="left">
        <h3 class="title">Paramètres :</h3>
        
		<!-- Formulaire de modification -->
        <form action="<?php echo $this->url; ?>&action=updatemap" method="post">        
            <p id="Mg-title-error" style="color:red;display:none;">Entrez un titre, svp</p> 
            <p>Titre* :<br><input type="text" id="Mg-title" name="Mg-title" value="<?php echo $quiz->titre; ?>"></p>
           
            <p id="Mg-question-error" style="color:red;display:none;">Entrez une question, svp</p>
            <p>Latitude* :<br><input type="text" id="Mg-question" name="Mg-question" value="<?php echo $quiz->question ?>"></p>
              
            <p id="Mg-reponse-error" style="color:red;display:none;">Entrez une réponse, svp</p>
            <p>Longitude* :<br><input type="text" id="Mg-reponse" name="Mg-reponse" value="<?php echo $quiz->reponse ?>"></p>

            <p id="Mg-resultat-error" style="color:red;display:none;">Entrez un résultat, svp</p>
            <p>Longitude* :<br><input type="text" id="Mg-resultat" name="Mg-resultat" value="<?php echo $quiz->resultat ?>"></p>
                            
            <input type="hidden" name="Mg-id" id="Mg-id" value="<?php echo $map->id; ?>">
              			 
            <p><input type="submit" name="btn-update" id="bt-quiz" class="button button-primary" value="Mettre à jour"></p>
            <small>* champs obligatoires</small>    
        </form>
        
		<!-- Formulaire de suppression -->
        <form action="<?php echo $this->url; ?>&action=deletequiz" method="post">
            <input type="hidden" name="Mg-id" value="<?php echo $quiz->id; ?>">
            <p><input type="submit" name="btn-delete" id="bt-delete" class="button button-primary" value="Supprimer le quiz"></p>
         </form> 
    </div> <!--fin .left -->
    
    <!-- Aperçu du quiz -->
    <div class="left">        
      <h3  class="title" >Aperçu :</h3>
      <!-- class 'map-display' : les cartes doivent avoir une hauteur minimum de 400px pour être affichées -->
      <div id="quiz" class="quiz-display"></div>
         <script type="text/javascript">
         // On initialise la latitude et la longitude de Paris
         // La carte sera centrée sur ce point
         var quest = <?php echo $quiz->question; ?>;
         var rep = <?php echo $quiz->reponse; ?>;
         var res = <?php echo $quiz->resultat; ?>;
         var monquiz = null;
        
         // Fonction d'initialisation de la carte
         function initQuiz() {
            // Créer l'objet "mon quiz" et l'insèrer dans l'élément HTML qui a l'ID "quiz"
             monquiz = L.quiz('quiz').setView([quest, rep, res], 100);
        
            // Leaflet ne récupère pas les cartes (tiles) sur un serveur par défaut. Nous devons lui préciser où nous souhaitons les récupérer. Ici, openstreetmap.fr
            L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', 
        	{
        		// Lien vers la source des données
        		attribution: 'données © <a href="//osm.org/copyright">Quiz</a>/ODbL - rendu <a href="//openstreetmap.fr">Quiz</a>',
        		minZoom: 1,
        		maxZoom: 20
            }).addTo(macarte);
        	
        	// Nous ajoutons un marqueur (= punaise)
        	var marker = L.marker([quest, rep, res]).addTo(monquiz);
        }
        
        // Fonction d'initialisation qui s'exécute lorsque le DOM est chargé
        window.onload = function() {
        	initQuiz();
        };
        </script>  
                   
    </div> <!--fin .left -->
</div><!--fin #contentmap2-->   