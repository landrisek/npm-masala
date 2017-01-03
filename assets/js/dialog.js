var app = angular.module('Dialog', ['ngDialog']);
app.config(['ngDialogProvider', function (ngDialogProvider) {
			ngDialogProvider.setDefaults({
				className: 'ngdialog-theme-default',
				plain: false,
				showClose: true,
				closeByDocument: true,
				closeByEscape: true,
				appendTo: false
			});
}]);
app.controller('MainCtrl', function ($scope, $rootScope, ngDialog) {
    $rootScope.jsonData = '{"foo": "bar"}';
    $rootScope.theme = 'ngdialog-theme-default';
    $scope.openConfirm = function (id) {
	ngDialog.openConfirm({
        	template: 'modalDialogId-' + id,
                className: 'ngdialog-theme-default'
		}).then(function (value) {
                    console.log('Modal promise resolved. Value: ', value);
		}, function (reason) {
			console.log('Modal promise rejected. Reason: ', reason);
		});
    };
    $scope.openTimed = function () {
	var dialog = ngDialog.open({
            template: '<p>Proces proběhl v pořádku.</p>',
            plain: true,
            closeByDocument: false,
            closeByEscape: false
	});
        setTimeout(function () {
            dialog.close();
	}, 2000);
    };
    $rootScope.$on('ngDialog.opened', function (e, $dialog) {
	console.log('ngDialog opened: ' + $dialog.attr('id'));
    });
    $rootScope.$on('ngDialog.closed', function (e, $dialog) {
	console.log('ngDialog closed: ' + $dialog.attr('id'));
    });
});
app.controller('InsideCtrl', function ($scope, ngDialog) {
    $scope.dialogModel = {
	message : 'message from passed scope'
    };
    $scope.openSecond = function () {
	ngDialog.open({
        	template: '<h3><a href="" ng-click="closeSecond()">Okno zavřete kliknutím sem</a></h3>',
		plain: true,
		closeByEscape: false,
		controller: 'SecondModalCtrl'
	});
    };
});
app.controller('SecondModalCtrl', function ($scope, ngDialog) {
    $scope.closeSecond = function () {
	ngDialog.close();
    };
});