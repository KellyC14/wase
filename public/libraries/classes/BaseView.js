var BaseView = function(options) {
    options = options || {};
};
_.extend(BaseView.prototype, {
    buildTag: function(tag, options) {
        var id = options.id ? ' id="'+options.id+'"' : '';
        var value = options.value || '';
        return '<'+tag+id+'>'+value+'</'+tag+'>';
    },
    init: function() {
        
    },
    display: function() {
        
    }
});