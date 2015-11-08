'use strict';

angular.module("app.controllers").controller('NavigationController', function ($scope, $http, $localStorage, $location) {
  $scope.location = $location;

  $scope.logout = function() {
    delete $http.defaults.headers.common['X-Auth-Token'];
    delete $scope.$root.user;
    delete $localStorage.token;
    delete $localStorage.user;
    $location.path("/login");
  };

  $(document).on('click', function (){
    $scope.$apply('systemDropdown = false');
  });
});
