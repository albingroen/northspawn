<?php
  session_start(); // Starting session


  // Connecting to the database for Northspawn project
  $db = new PDO("sqlite:Northspawn.db"); 

  // Destroying the session once the user clicks on logout 
  if(!empty($_GET['logout'])){
    session_unset();
    session_destroy();
  }

  // Logging in a user
  $email = null;
  $password = null;
  
  if(!empty($_POST["email"]) && !empty($_POST["password"]) && !empty($_POST['loginCheck']) ){
    // Declaring the post paramters for input by user on login
    $email = htmlspecialchars($_POST["email"]);  
    $password = htmlspecialchars($_POST["password"]);    

    // Findig the password for the account where the email matches the one written by the user
    $stmtDbPass = $db->prepare("SELECT user_password FROM users WHERE user_email = '{$email}'");
    $stmtDbPass->execute();
    $passwordDb = $stmtDbPass->fetch();
    

    // Checking if the input details matches with the databse details for that email,
    // and then storing the email in the session
    if(password_verify($password, $passwordDb[0])){      
      $_SESSION['user'] = $email;      
    $stmtAdmin = $db->prepare("SELECT admin FROM users WHERE user_email = '{$_SESSION['user']}'");
    $stmtAdmin->execute();
    $admin = $stmtAdmin->fetch();    
    $_SESSION['admin'] = $admin[0];    
    } else {      
      header("Location: index.php?pageid=login&err=TRUE");
      exit();      
    }
  }

  // Getting the name for that emails specific user, and declare it to a displayUser variable
  if(!empty($_SESSION['user'])){
    $user_email = $_SESSION['user'];
    $stmt = $db->prepare("SELECT user_firstName FROM users WHERE user_email = '{$user_email}'");
    $stmt->execute();
    $user = $stmt->fetch();
    $displayUser = $user[0];
  } else {
    $user_email = null;
    $displayUser = null;
  }

  // If there is no pageid it's going to route to index.php?pageid=landing in the background
	if(empty($_GET['pageid']))
	{
    $pageid = "landing";
	}
	else
	{
    $pageid = htmlspecialchars($_GET['pageid']);	
  }
  
  if(!empty($_POST['accept'])){
    $_SESSION['cookies'] = TRUE;
  } else {
    $_SESSION['cookies'] = FALSE;
  }

  // Adding user to database
  $firstName = null;
  $lastName = null;
  
  if(isset($_POST["firstName"]) && isset($_POST["lastName"]) && isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["confirm-password"])){
    $firstName = htmlspecialchars($_POST["firstName"]);
    $lastName = htmlspecialchars($_POST["lastName"]);
    $email = htmlspecialchars($_POST["email"]);
    $password = password_hash(htmlspecialchars($_POST['password']), PASSWORD_DEFAULT);
    $confirmedPasword = $_POST['confirm-password'];

    if(htmlspecialchars($_POST['password']) === $confirmedPasword){
      $stmt = $db->prepare("INSERT INTO users (user_firstName, user_lastName, user_email, user_password) VALUES ('{$firstName}', '{$lastName}', '{$email}', '{$password}')");
      $stmt->execute();
    }

    // Send a email after registration to user    
    $msg = "First line of text\nSecond line of text";
    $msg = wordwrap($msg,70);    
    $subject = "Välkommen till Northspawn.se!";
    mail("albin.groen@gmail.com", $subject, $msg); // usign $email from register to send to
  }

  // Adding feedback to database
  if(!empty($_POST['feedback'])){
    if(!empty($_SESSION['user'])){
      $feedback = htmlspecialchars($_POST['feedback']);
      $stmtFeedback = $db->prepare("INSERT INTO feedback (text, author) VALUES ('{$feedback}', '{$_SESSION['user']}')");
      $stmtFeedback->execute();
      header("Location: index.php");
      exit();
    } else {      
      $feedback = htmlspecialchars($_POST['feedback']);
      $stmtFeedback = $db->prepare("INSERT INTO feedback (text, author) VALUES ('{$feedback}', 'Anonym')");
      $stmtFeedback->execute();
      header("Location: index.php");
      exit();
    }
  }
	
	// Building page depending on GET parameters to index.php
  require("incs/header/header.php");	

  require("{$pageid}.php");

  require("incs/footer/footer.php");	   
  
  

  ?>
  <head>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.12/css/solid.css" integrity="sha384-VxweGom9fDoUf7YfLTHgO0r70LVNHP5+Oi8dcR4hbEjS8UnpRtrwTx7LpHq/MWLI" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.12/css/fontawesome.css" integrity="sha384-rnr8fdrJ6oj4zli02To2U/e6t1qG8dvJ8yNZZPsKHcU7wFK3MGilejY5R/cUc5kf" crossorigin="anonymous">
    
    <style>
      #chatWindow {
        position: fixed;
        height: 680px;
        max-width: 370px;
        right: 30px;
        bottom: 100px;
        background: white;
        border-radius: 5px;
        display: none;
        transition: .3s ease-out 0s;
        opacity: 0;
        -webkit-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        -moz-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        overflow-y: scroll;
      }
      #chatWindow header {
        background: #311C49;
        width: calc(100% - 60px);
        height: 200px;
        padding: 30px;
        padding-top: 20px;
        display: flex;    
        color: white;
        flex-direction: column;
        border-radius: 5px 5px 0px 0px;                
      }
      #logo {
        width: 120px;        
      }
      #chatWindow header h2 {
        font-size: 28px;
        color: white;
        padding-top: 10px;
        font-weight: normal;
        <?php 
          if(!empty($_SESSION['user'])){
            echo "text-transform: capitalize";
          }
        ?>
      }
      #chatWindow form {
        width: 90%;
        margin: 0 auto;
      }
      .content-cards {
        width: 90%;
        margin: 0 auto;
      }
      .modal {
        background: white;
        border-radius: 3px;
        width: calc(100% - 30px);        
        min-height: 100px;
        margin-top: -50px;
        -webkit-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        -moz-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        padding: 15px;
        border-top: 2px solid dodgerblue;
        margin-bottom: 70px;
      }
      .modal input {        
        height: 40px;
        padding-left: 15px;
        padding-right: 15px;
        width: calc(100% - 30px);
        background: #FAFAFA;
        border-radius: 3px;
        border: 1px solid #E1E1E1;
        font-size: 15px;
        transition: .2s ease-out 0s;
      }
      .modal input::placeholder {
        opacity: .65;
      }
      .modal input:focus {
        outline: none;
        background: #f2efef;
        transition: .2s ease-out 0s;
      }
      .modal-title {
        color: #333;
        padding-bottom: 5px;
        font-size: 16px;
      }
      .modal p {
        font-size: 13px;
        opacity: .7;
        line-height: 18px;
        padding-right: 20px;
      }
      .modal button {
        height: 40px;
        padding-left: 15px;
        padding-right: 15px;
        width: 50%;
        margin-top: 18px;
        background: #266EFA;
        color: white;
        border-radius: 50px;
        border: none;
        font-size: 15px;
        cursor: pointer;
      }
      #chatWindowBtn {
        height: 75px;
        width: 75px;
        background: #266EFA;
        border-radius: 50%;
        position: fixed;
        bottom: 30px;
        right: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 30px;
        -webkit-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        -moz-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
      }
      #chatWindowBtn2 {
        height: 75px; 
        width: 75px;
        background: #266EFA;
        border-radius: 50%;
        position: fixed;
        bottom: 30px;
        right: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 30px;
        display: none;
        -webkit-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        -moz-box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        box-shadow: 0px 10px 50px 0px rgba(0,0,0,0.1);
        transform: rotate(-180deg);
        transition: .3s linear 0s;
      }
      .contact-link {
        color: dodgerblue;
        text-decoration: none;
        margin-bottom: 5px;
        display: inline-block;
        font-size: 14px;
      }
      
    </style>
  </head>
  <div id="chatWindow">
    <header>
      <img src="./imgs/Northspawn_logo_vit.png" id="logo" alt="Logotyp">
      <h2>Hej, 
      <?php 
        if(!empty($_SESSION['user'])){
          echo $displayUser , " 👋";
        } else {
          echo "ge oss gärna feedback 👍";
        }      
      ?>          
      </h2>
    </header>
    <form method="post">
      <div class="modal">
        <input name="feedback" type="text" placeholder="Feedback" min="1" max="500" required  >
        <button>Skicka feedback</button>
      </div>                      
    </form>
    <div class="content-cards">
      <div class="modal">
        <h3 class="modal-title" >Feedback</h3>
        <p>Här kan du skicka in feedback eller ställa frågor till teamet på Northspawn. Alla frågor besvaras så snabbt som möjligt.</p>
      </div>

      <div class="modal">
        <h3 class="modal-title" >Kontakt</h3>
        <div>
          <a class="contact-link" href="">info@northspawn.se</a>
        </div>
        <div>
          <a class="contact-link" href="">Kopparbergsvägen 8, 722 13 Västerås</a>
        </div>
      </div>      
      
      <div class="modal">
        <h3 class="modal-title" >Om sidan</h3>        
        <div>
          <p>Den här webbplatsen är ett elevproducerat slutprojekt i kursen webbserverprogrammering på Wijkmanska gymnasiet vårterminen 2018. Den ska inte förväxlas med den officiella sidan för Nortspawn-eventet som kan hittas på <a target="blank" href="https://www.northspawn.se/">northspawn.se</a>. Är du nyfiken på webbutveckling eller inriktningen IT & Media på teknikprogrammet? Gå till <a target="blank" href="http://www1.vasteras.se/wijkmanska/">wijkmanska.se</a> för att få mer info.</p>
        </div>
      </div>
    </div>
  </div>
  <div onClick="openWindow()" id="chatWindowBtn"><i class="fas fa-comment-alt"></i></div>  
  <div onClick="closeWindow()" id="chatWindowBtn2"><i class="fas fa-times"></i></div>  

  <script>      
    function openWindow() {
      document.getElementById("chatWindow").style.display = "block";
      document.getElementById("chatWindowBtn").style.display = "none";
      document.getElementById("chatWindowBtn2").style.display = "flex";            
      setTimeout(() => {
        document.getElementById("chatWindow").style.opacity = 1;
        document.getElementById("chatWindow").style.bottom = '130px';
        document.getElementById("chatWindowBtn2").style.transform = "rotate(0deg)";
      }, 100);
    }    
    document.getElementById("chatWindowBtn").addEventListener("click", openWindow);

    function closeWindow() {                 
      document.getElementById("chatWindow").style.opacity = 0;
      document.getElementById("chatWindow").style.bottom = '100px';      
      document.getElementById("chatWindowBtn").style.display = "flex";
      document.getElementById("chatWindowBtn2").style.transform = "rotate(-180deg)";
      setTimeout(() => {
        document.getElementById("chatWindow").style.display = "none";        
        document.getElementById("chatWindow").style.opacity = 0;   
        document.getElementById("chatWindowBtn2").style.display = "none";    
      }, 500);
    }    
    document.getElementById("chatWindowBtn2").addEventListener("click", closeWindow);
  </script>

  <style>
    <?php include('styles/landing/style.css') ?>
  </style>
