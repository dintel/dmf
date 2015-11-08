'use strict';

angular.module("app.controllers").controller('LoginController', function($scope, $http, $location, $localStorage) {
  $scope.checkbox = {
    remember: false
  };
  $scope.error = {
    invalidLogin: false,
    serverError: false
  };
  $scope.checking = false;

  $scope.httpErrorHandler = function (error) {
    $.bootstrapGrowl(error.message,{'type':"danger",delay:3000,width:'auto',align:'center'});
    console.log(error);
  };

  $scope.login = function() {
    var login = $("#login").val();
    var password = $("#password").val();
    var loginPromise = $http.post('/auth/login', {login: login, password: password});
    $scope.checking = true;

    loginPromise.success(function(response, status, headers, config){
      if (typeof response == "object") {
        if (response.result && response.result.token) {
          if ($scope.checkbox.remember) {
            $localStorage.token = response.result.token;
            $localStorage.user = response.result.user;
          }
          $http.defaults.headers.common['X-Auth-Token'] = response.result.token;
          $scope.$root.user = response.result.user;
        } else {
          console.log(response.message);
        }
      } else {
        console.log(response);
      }
    });
    loginPromise.error($scope.httpErrorHandler);
    loginPromise.finally(function(){
      $scope.checking = false;
      if ($http.defaults.headers.common['X-Auth-Token']) {
        $location.path("/");
      }
    });
  };
});
