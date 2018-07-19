var app = new Vue({
    el: '#app',
    data: {
        wallets: []
    },
    mounted() {
        this.getMyData();
    },
    methods: {
        getMyData: function (){
            axios.post('http://localhost:8080', {
                jsonrpc: '2.0',
                method: 'getWallets',
                params: {},
                id: 1
            }).then(results => {
                console.log(results.data);
                this.wallets = results.data.data
            }).catch(error => {
                console.log(error, 'error');
            });
        }
    }
});

$(document).ready(function () {

    $(document).off('click', '#create-wallet').on('click', '#create-wallet', function (e) {
        e.preventDefault();
        console.log('Create Wallet!!');
        let walletAlias = $('#wallet-alias').val();
        axios.post('http://localhost:8080', {
            jsonrpc: '2.0',
            method: 'createWallet',
            params: {alias: walletAlias},
            id: 1
        }).then(results => {
            app.getMyData();
            $('#wallet-alias').val('');
        }).catch(error => {
            console.log(error, 'error');
        });
    });
});