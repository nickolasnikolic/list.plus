<?php
require 'vendor/autoload.php';

//for amazon
use ApaiIO\ApaiIO;
use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\Operations\CartAdd;
use ApaiIO\Operations\CartCreate;
//for mail
use Mailgun\Mailgun;


error_reporting(-1);//tell me stuff

$app = new \Slim\Slim();

$app->get('/home', function(){

  //get the administrator

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );

});

$app->post('/home', function(){

  if( !empty($_POST['username']) && !empty($_POST['password']) ){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

    $server = $url["host"];
    $user = $url["user"];
    $pass = $url["pass"];
    $database = substr($url["path"], 1);

    $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
    $stmtUserId = $db->prepare('SELECT userid, email FROM users WHERE email = :administrator AND password = :password;');
    $stmtUserId->bindParam( ':administrator', $username );
    $stmtUserId->bindParam( ':password', $password );
    $stmtUserId->execute();
    $resultUserId = $stmtUserId->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($resultUserId);

  }else{
    echo 0;
  }
});

$app->post('/password', function(){
  //get email from post info
  $email = $_POST['email'];
  //get password from db
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtPassword = $db->prepare('SELECT password FROM users WHERE email = :email;');
  $stmtPassword->bindParam( ':email', $email );

  $stmtPassword->execute();
  $resultPassword = $stmtPassword->fetchAll(PDO::FETCH_ASSOC);

  //email password to email given, if exists
  if(isset($resultPassword[0]['password'])) {

    $password = $resultPassword[0]['password'];

    # First, instantiate the SDK with your API credentials and define your domain.
    $msg = new Mailgun(getenv("MAILGUN_API_KEY"));
    $domain = getenv('MAILGUN_DOMAIN');

    # Now, compose and send your message.
    $msg->sendMessage($domain, array(
        'from'    => 'info@quick.plus',
        'to'      => $email,
        'subject' => 'Your quick.plus password request.',
        'text'    => "Your requested password is: \n{$password}"
    ));

    echo 'done';
  }else{
    echo 'nogo';
  }

});

$app->post('/home/register/', function(){

  if( !empty($_POST['email']) && !empty($_POST['password']) ){ //HACK

    $username = $_POST['email'];
    $password = $_POST['password'];


    $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

    $server = $url["host"];
    $user = $url["user"];
    $pass = $url["pass"];
    $database = substr($url["path"], 1);

    $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
    $stmtUserId = $db->prepare('INSERT INTO users ( email, password ) VALUES (:email, :password)');
    $stmtUserId->bindParam( ':email', $username );
    $stmtUserId->bindParam( ':password', $password );
    $stmtUserId->execute();

    echo 1; //all is well
  }else{
    echo 0; //everything sucks and I quit...
  }

});

//new user
$app->get('/admin/:administrator', function($administrator){
  //get the administrator

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );
});

//add class
$app->post('/admin/add/class/', function(){
  //for this admin
  //pull the class details from the post
  $administrator = $_POST['admin'];
  $class = $_POST['title'];
  $number = $_POST['number'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('INSERT INTO lists (name, list_owner) VALUES (:name, :prof);');
  $stmtUserId->bindParam( ':name', $class );
  $stmtUserId->bindParam( ':number', $number );
  $stmtUserId->bindParam( ':prof', $administrator );
  $stmtUserId->execute();

  //return the list of classes with generated ids intact
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode($resultClasses);
});

//delete class
$app->post('/admin/remove/class/', function(){
  //for this admin
  //pull the class details from the post
  $id = $_POST['id'];

  //set them in the db
  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('DELETE FROM lists WHERE id = :id;');
  $stmtUserId->bindParam( ':id', $id );
  $stmtUserId->execute();

});

//add book //TODO not enough values inserted
$app->post('/admin/add/book/', function(){
  //for this admin
  //pull the class details from the post
  $name = $_POST['title'];
  $isbn = $_POST['isbn'];
  $classid = $_POST['class'];
  $administrator = $_POST['admin'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('INSERT INTO items (title, isbn, list) VALUES (:title, :isbn, :class);');
  $stmtUserId->bindParam( ':title', $name );
  $stmtUserId->bindParam( ':isbn', $isbn );
  $stmtUserId->bindParam( ':class', $classid );
  $stmtUserId->execute();

  //get the classes an administrator teaches
  $stmtClasses = $db->prepare('SELECT * FROM lists WHERE list_owner = :administrator;');
  $stmtClasses->bindParam( ':administrator', $administrator );
  $stmtClasses->execute();
  $resultClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC);

  //then for each
  foreach ($resultClasses as &$class) {
    //get the books from that class
    $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :classid;');
    $stmtBooks->bindParam( ':classid', $class['id'] );
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);
    //then attach them to the appropriate class for this administrator
    $class['items'] = $resultBooks;
  }

  //return as json
  echo json_encode( $resultClasses );
});

//delete book
$app->post('/admin/remove/book/', function(){
  //for this admin
  //pull the class details from the post
  $bookid = $_POST['bookid'];

  //set them in the db

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtUserId = $db->prepare('DELETE FROM items WHERE id = :bookid;');

  $stmtUserId->bindParam( ':bookid', $bookid );

  $stmtUserId->execute();

});

//where the amazon magic happens
$app->get('/bouncy/:administrator/:thisclassid', function($administrator, $thisclassid){

  //get the administrator

  $url = parse_url(getenv("CLEARDB_DATABASE_URL"));

  $server = $url["host"];
  $user = $url["user"];
  $pass = $url["pass"];
  $database = substr($url["path"], 1);

  //get the books from that class
  $db = new PDO("mysql:host=$server;dbname=$database;charset=utf8", $user, $pass);
  $stmtBooks = $db->prepare('SELECT * FROM items WHERE list = :class');
  $stmtBooks->bindParam( ':class', $thisclassid );
  $stmtBooks->execute();
  $resultingBooks = $stmtBooks->fetchAll(PDO::FETCH_ASSOC);

  //send those books over to amazon
  //start the request to create the cart
  $conf = new GenericConfiguration();

  $conf
      ->setCountry('com')
      ->setAccessKey(getenv('AMAZON_ACCESS'))
      ->setSecretKey(getenv('AMAZON_SECRET'))
      ->setAssociateTag(getenv('AMAZON_ASSOCIATE_TAG'));

  $apaiIO = new ApaiIO($conf);

  $cartCreate = new CartCreate();

  //for each of the books in question
  //build the individual portions of the request
  foreach ($resultingBooks as $book ) {

      $cartCreate->addItem($book['isbn'], 1);

  }

  //complete the request, creating the cart
  $response = $apaiIO->runOperation($cartCreate);

  $placeToGo = simplexml_load_string($response);

  $url = $placeToGo->Cart->PurchaseURL[0];

  if(!empty($url)){
    //finally redirect to amazon where the cart has been created
    header('location: ' . $url );
    exit();
  }else{
    echo '<h2>The cart couldn\'t be created.</h2>';
  }
});

$app->post('/contact', function(){
  //send message in content
  $message = $_POST['name'];
  $message .= $_POST['phone'];
  $message .= $_POST['message'];

  echo $message;
});

$app->run();
