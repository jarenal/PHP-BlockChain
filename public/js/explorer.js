var app = new Vue({
    el: '#app',
    data: {
        blocks: []
    },
    mounted() {
        this.getMyData();
    },
    methods: {
        getMyData: function (){
            axios.post('http://localhost:8080', {
                jsonrpc: '2.0',
                method: 'getBlocks',
                params: {},
                id: 1
            }).then(results => {
                console.log(results.data);
                this.blocks = results.data.data
            }).catch(error => {
                console.log(error, 'error');
            });
        }
    }
});