blindApp.controller('IndexController', ['$scope', '$state', 'globals', function($scope, $state, globals) {}])

blindApp.controller('HomeController', ['$scope', '$state', 'globals', function($scope, $state, globals) {

  //collect user/pass
  //if login is super secret
  //move to admin
  $scope.login = function(){

    var username = $('#username').val();
    var password = $('#password').val();

    $.post('../api/home',{ 'username': username, 'password': password })
      .success(function(data){

        if(data != 0){
          var holdingCell = JSON.parse(data);
          globals.setUserId( holdingCell[0].userid );
          globals.setUserEmail( username );

          $state.go('admin');
        }
      })
      .error(function(error){
        console.log('error is: ');
        console.log(error);
      });

  };

  //collect user/pass
  //if login is super secret
  //move to admin
  $scope.register = function(){

    var username = $('#registrationEmail').val();
    var password = $('#registrationPassword').val();
    var nickname = $('#registrationNickname').val();

    $.post('../api/home/register/',{ 'email': username, 'password': password, 'nickname': nickname })
      .success(function(data){
        if(data != 0){
          var holdingCell = JSON.parse(data);
          globals.setUserId( holdingCell[0].userid );
          globals.setUserEmail( username );
        }
      })
      .error(function(error){
        console.log('error is: ');
        console.log(error);
      });

  };

  $scope.passwordReminder = function(){
    var email = $('#passwordEmail').val();
    $('#passwordEmail').val('');

    $.post('../api/password',{ 'email': email })
        .error(function(error){
          console.log('error is: ');
          console.log(error);
        });

  };

  $scope.shareFacebook = function( userId, listId ){
    FB.ui({
      method: 'share',
      href: 'http://list.plus/app/#/shared/' + userId + '/' + listId,
    }, function(response){});
  };

  //go get the lists
  $.get('../api/home' )
      .success(function(data){
        $scope.lists = JSON.parse(data);
        $scope.$apply();
      });

}])

blindApp.controller('AdminController', ['$scope', '$state', 'globals', function($scope, $state, globals) {

  //if the username isn't set, return home for login
  if( globals.getUserId() == '' ){
    $state.go('home');
  }

  $scope.classes = [];

  $.get('../api/admin/' + globals.getUserId() )
    .success(function(data){
      $scope.classes = JSON.parse(data);
      $scope.$apply();
    });

  //simple logout
  $scope.logout = function(){
    globals.setUserId(null);
    $state.go('home');
  };

  //on add class
  //add class
  $scope.addClass = function(){
    var classTitle = $('#classTitle').val();
    var classNumber = $('#classNumber').val();
    var admin = globals.getUserId();

    $('#classTitle').val('');
    $('#classNumber').val('');

    $.post('../api/admin/add/class/',{ 'title': classTitle, 'number': classNumber, 'admin': admin })
        .success(function(data){
          $scope.classes = JSON.parse(data);
          $scope.$apply();
        })
        .error(function(error){ console.log(error) });
  };

  //on remove class
  //remove class
  $scope.removeClass = function(index, classid){
    $scope.classes.splice( index, 1 );
    $.post('../api/admin/remove/class/',{ 'id': classid });
  };

  //prep for book nested array
  var source = 0;
  var classIndex = 0;
  $scope.frustrating = function(setTheBastard, classId){
    source = setTheBastard;
    classIndex = classId;
  };

  //on add book
  //add book
  $scope.addBook = function(){

    var bookTitle = $('#bookTitle').val();
    var bookIsbn = $('#bookIsbn').val();
    var admin = globals.getUserId();

    $('#bookTitle').val('');
    $('#bookIsbn').val('');

    //add to db
    $.post('../api/admin/add/book/',{ 'title': bookTitle, 'isbn': bookIsbn, 'class': classIndex, 'admin': admin })
        .success(function(data){
          $scope.classes = JSON.parse(data); //HACK refreshing whole list when single book is added
          $scope.$apply();
        })
        .error(function(error){ console.log(error) });
  };

  //on remove book
  //remove book
  $scope.removeBook = function(index, which){

    var book = $scope.classes[which].items[index];

    $scope.classes[which].items.splice( index, 1 );

    //remove from db
    $.post('../api/admin/remove/book/',{ 'bookid': book.id })
        .error(function(error){console.log(error)});
  };

  $scope.qrcodeurl = '';
  //on generate
  //move to display with cart qr code
  $scope.generateQrCode = function( whichClass ){
    $('#qrcode').empty();

    $scope.qrcodeurl = 'https://quickplus.herokuapp.com'; //site root...
    $scope.qrcodeurl += '/api/bouncy/';

    var thisClass = $scope.classes[whichClass];

    $scope.qrcodeurl +=  + globals.getUserId() + '/' + thisClass.id;
    new QRCode( document.getElementById("qrcode"), $scope.qrcodeurl );
  };

}])

blindApp.controller('SharedController', ['$scope', '$state', '$stateParams', function($scope, $state, $stateParams) {

  var userId = $stateParams.userId;
  var listId = $stateParams.listId;
  //go get the lists
  $.get('../api/shared/' + userId + '/' + listId )
      .success(function(data){
        console.log(data);
        $scope.list = JSON.parse(data);
        $scope.$apply();

        new ShareButton({
          url: 'http://www.list.plus/app/#/shared/' + $scope.list.list_owner + '/' + $scope.list.id,
          title: $scope.list.title,
          ui:{
            flyout: 'bottom right'
          },
          networks: {
            facebook: {
              app_id: "865694990192440"
            },
            email:{ enabled: false },
            pinterest:{ enabled: false },
            reddit:{ enabled: false },
            whatsapp:{ enabled: false },
            email:{ enabled: false }
          }
        });

      });

}])

blindApp.controller('ContactController', ['$scope', '$state', function($scope, $state) {}])
