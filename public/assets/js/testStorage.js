var TestStorage = {

    _testKey: 'test_v2',
    _testProgressKey: 'testProgress_v2',

    saveTestProgress: function(progress) {
        return localStorage.setItem(this._testProgressKey, JSON.stringify(progress));
    },

    loadTestProgress: function() {
        return JSON.parse(localStorage.getItem(this._testProgressKey));
    },

    testInProgressExists: function() {
        return localStorage.getItem(this._testKey) !== null && localStorage.getItem(this._testProgressKey) !== null;
    },

    clearTestAndProgress: function() {
        localStorage.removeItem(this._testKey);
        localStorage.removeItem(this._testProgressKey);
    },
    
    saveTest: function(testContent) {
        localStorage.setItem(this._testKey, testContent);
    }

}