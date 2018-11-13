describe('Form', () => {
    it('Axios return response with lower case data so each response.data in Form willl work', () => {
        var axios = require('../../../../node_modules/axios/lib/core/Axios.js');
        var get = ('' + axios.prototype['get']).replace(/\s/g, '')
        expect(get).toEqual('function(url,config){returnthis.request(utils.merge(config||{},{method:method,url:url}));}')
        var post = ('' + axios.prototype['post']).replace(/\s/g, '')
        expect(post).toEqual('function(url,data,config){returnthis.request(utils.merge(config||{},{method:method,url:url,data:data}));}')
    });
});