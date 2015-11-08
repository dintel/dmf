describe('BodyController', function(){

  beforeEach(module('app'));

  it('should create "phones" model with 3 phones', inject(function($controller) {
    var scope = {},
        ctrl = $controller('BodyController', {$scope:scope});

    expect(scope.phones.length).toBe(3);
  }));

});
