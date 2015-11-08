app.directive('collectionSelect', ['$rootScope', 'collections', function ($rootScope, collections) {
  return {
    templateUrl: 'partials/collectionSelect.html',
    restrict: 'E',
    replace: true,
    scope: {
      value: '=',
      enabled: '='
    },
    link: {
      pre: function (scope, element, attrs) {
        scope.withRefresh = attrs.withRefresh == "true" ? true : false;
        scope.collection = collections.getCollection(attrs.name);
        scope.fieldValue = typeof attrs.fieldValue == 'string' ? attrs.fieldValue : 'id';
        scope.fieldName = typeof attrs.fieldName == 'string' ? attrs.fieldName : 'name';
        scope.query = "record."+scope.fieldValue+" as record."+scope.fieldName+" for record in collection";

        if (scope.enabled === undefined) {
          scope.enabled = true;
        }

        $rootScope.$on('collectionUpdate.'+attrs.name, function (e){
          scope.collection = collections.getCollection(attrs.name);
          scope.setDefaultValue();
          scope.updateDisabled();
        });

        scope.setDefaultValue = function () {
          if (scope.value === undefined && scope.collection !== undefined && scope.collection.length != 0) {
            scope.value = scope.collection[0][scope.fieldValue];
          }
        };

        scope.refresh = function () {
          collections.refresh(attrs.name);
        };
        scope.setDefaultValue();
      },
      post: function (scope, element, attrs) {
        scope.updateDisabled = function () {
          $('.toggle', element).prop('disabled', !scope.enabled);
        };

        scope.$watch('enabled', scope.updateDisabled);
        scope.updateDisabled();
      }
    }
}}]);
