<template>
    <div id="app">
        <div v-if="!user" class="login-container">
            <LoginForm @login="handleLogin" />
        </div>
        <div v-else class="app-container">
            <Header :user="user" @logout="handleLogout" />
            <div class="main-content">
                <BalanceSection :balance="currentBalance" />
                <TransferForm 
                    :users="users" 
                    @transfer="handleTransfer" 
                    :loading="transferLoading" 
                />
                <TransactionHistory 
                    :transactions="transactions" 
                    :loading="transactionsLoading" 
                />
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import Pusher from 'pusher-js';
import Header from './components/Header.vue';
import LoginForm from './components/LoginForm.vue';
import BalanceSection from './components/BalanceSection.vue';
import TransferForm from './components/TransferForm.vue';
import TransactionHistory from './components/TransactionHistory.vue';

export default {
    name: 'App',
    components: {
        Header,
        LoginForm,
        BalanceSection,
        TransferForm,
        TransactionHistory,
    },
    data() {
        return {
            user: null,
            users: [],
            transactions: [],
            currentBalance: 0,
            transactionsLoading: false,
            transferLoading: false,
            pusher: null,
        };
    },
    async mounted() {
        const token = localStorage.getItem('authToken');
        if (token) {
            await this.fetchUser();
            await this.fetchTransactions();
            await this.fetchUsers();
            this.initPusher();
        }
    },
    methods: {
        async fetchUser() {
            try {
                const response = await axios.get('/api/user');
                this.user = response.data;
                this.currentBalance = parseFloat(response.data.balance);
            } catch (error) {
                this.handleAuthError();
            }
        },
        async fetchTransactions() {
            this.transactionsLoading = true;
            try {
                const response = await axios.get('/api/transactions');
                this.transactions = response.data.transactions.data;
                this.currentBalance = parseFloat(response.data.current_balance);
            } catch (error) {
                console.error('Error fetching transactions:', error);
            } finally {
                this.transactionsLoading = false;
            }
        },
        async fetchUsers() {
            try {
                // In a real app, you'd have an endpoint to fetch other users
                // For now, we'll simulate this with a fixed list
                const response = await axios.get('/api/users'); // You'll need to create this endpoint
                this.users = response.data;
            } catch (error) {
                console.error('Error fetching users:', error);
            }
        },
        async handleLogin(credentials) {
            try {
                const response = await axios.post('/api/login', credentials);
                const { user, token } = response.data;
                
                this.user = user;
                this.currentBalance = parseFloat(user.balance);
                localStorage.setItem('authToken', token);
                
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
                
                await this.fetchTransactions();
                await this.fetchUsers();
                this.initPusher();
            } catch (error) {
                throw new Error('Invalid credentials');
            }
        },
        async handleLogout() {
            try {
                await axios.post('/api/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.user = null;
                this.transactions = [];
                this.currentBalance = 0;
                localStorage.removeItem('authToken');
                delete axios.defaults.headers.common['Authorization'];
                
                if (this.pusher) {
                    this.pusher.disconnect();
                }
            }
        },
        async handleTransfer(transferData) {
            this.transferLoading = true;
            try {
                await axios.post('/api/transactions', transferData);
                // Real-time update will handle the UI update
            } catch (error) {
                const message = error.response?.data?.message || 'Transfer failed';
                throw new Error(message);
            } finally {
                this.transferLoading = false;
            }
        },
        initPusher() {
            this.pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
                cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                forceTLS: true
            });

            const channel = this.pusher.subscribe(`user.${this.user.id}`);
            
            channel.bind('transaction.completed', (data) => {
                this.handleRealTimeUpdate(data);
            });
        },
        handleRealTimeUpdate(data) {
            // Update balance
            this.currentBalance = parseFloat(data.transaction.sender_id === this.user.id 
                ? this.currentBalance - parseFloat(data.transaction.total_amount)
                : this.currentBalance + parseFloat(data.transaction.amount)
            );

            // Add transaction to history
            this.transactions.unshift(data.transaction);

            // Show notification
            this.showNotification(data);
        },
        showNotification(data) {
            const type = data.transaction.sender_id === this.user.id ? 'sent' : 'received';
            const amount = parseFloat(data.transaction.amount).toFixed(2);
            const name = type === 'sent' 
                ? data.transaction.receiver.name 
                : data.transaction.sender.name;
            
            const message = type === 'sent' 
                ? `You sent $${amount} to ${name}`
                : `You received $${amount} from ${name}`;
            
            // Simple notification - you can enhance this with a proper notification system
            alert(message);
        },
        handleAuthError() {
            this.user = null;
            localStorage.removeItem('authToken');
            delete axios.defaults.headers.common['Authorization'];
        },
    },
};
</script>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f6fa;
    color: #333;
}

.login-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.app-container {
    min-height: 100vh;
}

.main-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 20px;
}

@media (max-width: 768px) {
    .main-content {
        grid-template-columns: 1fr;
    }
}
</style>