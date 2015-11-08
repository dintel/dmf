'use strict';

angular.module("app.controllers").controller('UserListController', function($scope, $http, hotkeys, jsonResponse) {
  $scope.selection = {};
  $scope.showEditModal = false;
  $scope.showDeleteModal = false;
  $scope.editModalTitles = {
    edit: "Edit user",
    create: "Create new user"
  };
  $scope.editModalTitle = $scope.editModalTitles.edit;
  $scope.editUser = {};
  $scope.users = new Array();

  $scope.defaultUser = {
    type: 'user',
    active: true
  };

  $scope.types = [
    {value: 'admin', name: 'Admin'},
    {value: 'user', name: 'Regular user'}
  ];

  hotkeys.add({
    combo: 'a',
    description: 'Add new user',
    callback: function (event, hotkey) {
      $scope.showEditUser({});
    }
  });

  hotkeys.add({
    combo: 'd',
    description: 'Delete selected users',
    callback: function (event, hotkey) {
      if ($scope.isSelected()) {
        $scope.showModal('showDeleteModal');
      }
    }
  });

  $scope.userTypeText = function (type) {
    if (type == "admin") {
      return "Administrator";
    } else if (type == "user") {
      return "User";
    }
    return "Unknown type";
  };

  $scope.activeText = function (active) {
    return active ? "Yes" : "No";
  };

  $scope.toggleModal = function (modal, param) {
    $scope[modal] = !$scope[modal];
  };

  $scope.showEditUser = function (user) {
    $scope.editUser = $.extend({}, user);
    $scope.editModalTitle = 'id' in user ? $scope.editModalTitles.edit : $scope.editModalTitles.create;
    $scope.toggleModal('showEditModal');
  };

  $scope.isSelected = function () {
    for (var name in $scope.selection) {
      if ($scope.selection[name]) {
        return true;
      }
    }
    return false;
  };

  $scope.updateUsers = function (users) {
    $scope.users = users;
  };

  $scope.refresh = function (result) {
    if (result) {
      $scope.showEditModal = false;
      $scope.showDeleteModal = false;
      jsonResponse.post("/user/index", {}, $scope.updateUsers);
    }
  };

  $scope.save = function () {
    if (!$scope.validateUser($scope.editUser)) {
      return;
    }
    if ($scope.editUser.password == "") {
      delete $scope.editUser.password;
    }
    delete $scope.editUser.password2;
    jsonResponse.post("/user/save", {data:$scope.editUser}, $scope.refresh);
  };

  $scope.validateUser = function (user) {
    if ('password' in user && user.password != "") {
      if (user.password.length < 8) {
        msg("error", "User password must be at least 8 characters long!");
        return false;
      }
      if (user.password != user.password2 ) {
        msg("error", "Passwords do not match!");
        return false;
      }
    }
    return true;
  };

  $scope.delete = function () {
    var users = new Array();
    for (var id in $scope.selection) {
      if ($scope.selection[id]) {
        users.push(id);
      }
    }
    jsonResponse.post("/user/delete", {ids: users}, $scope.refresh);
  };

  $scope.refresh(true);
});
