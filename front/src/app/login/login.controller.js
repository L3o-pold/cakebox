(function () {
    'use strict';

    angular.module('cakebox')
        .controller('LoginCtrl', LoginCtrl);

    function LoginCtrl($rootScope, $scope, $localStorage, Auth) {

        function successAuth(res) {
            $localStorage.token = res.token;
            window.location = "/#/browse";
        }

        $scope.signin = function () {
            var formData = {
                username: $scope.username,
                password: $scope.password
            };

            Auth.signin(formData, successAuth, function () {
                $rootScope.error = 'Invalid credentials.';
            });
        };

        $scope.logout = function () {
            Auth.logout(function () {
                window.location = "/";
            });
        };
        $scope.token = $localStorage.token;
        $scope.tokenClaims = Auth.getTokenClaims();
    }
})
();
