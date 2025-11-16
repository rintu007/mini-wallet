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
            notification: {
                show: false,
                message: '',
                type: 'success'
            }
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
                this.showNotification('Error loading transactions', 'error');
            } finally {
                this.transactionsLoading = false;
            }
        },
        async fetchUsers() {
            try {
                const response = await axios.get('/api/users');
                this.users = response.data;
            } catch (error) {
                console.error('Error fetching users:', error);
                this.showNotification('Error loading users', 'error');
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
                
                this.showNotification('Login successful!', 'success');
            } catch (error) {
                throw new Error('Invalid credentials');
            }
        },
        async handleLogout() {
            try {
                await axios.post('/api/logout');
                this.showNotification('Logged out successfully', 'success');
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
                const response = await axios.post('/api/transactions', transferData);
                
                // Show success message
                this.showNotification('Transfer completed successfully!', 'success');
                
                // Update balance immediately from response
                this.currentBalance = parseFloat(response.data.new_balance);
                
                // Refresh transactions list to include the new transaction
                await this.fetchTransactions();
                
            } catch (error) {
                const message = error.response?.data?.message || 'Transfer failed';
                this.showNotification(message, 'error');
                throw new Error(message);
            } finally {
                this.transferLoading = false;
            }
        },
        initPusher() {
            try {
                this.pusher = new Pusher(import.meta.env.VITE_PUSHER_APP_KEY, {
                    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
                    forceTLS: true
                });

                const channel = this.pusher.subscribe(`user.${this.user.id}`);
                
                channel.bind('transaction.completed', (data) => {
                    console.log('Pusher event received:', data);
                    this.handleRealTimeUpdate(data);
                });

                // Handle connection events
                this.pusher.connection.bind('connected', () => {
                    console.log('Pusher connected successfully');
                });

                this.pusher.connection.bind('error', (err) => {
                    console.error('Pusher connection error:', err);
                });

            } catch (error) {
                console.error('Pusher initialization error:', error);
            }
        },
        handleRealTimeUpdate(data) {
            console.log('Processing real-time update:', data);
            
            // Update balance based on transaction type
            if (data.transaction.sender_id === this.user.id) {
                // Sent money - subtract total amount
                this.currentBalance -= parseFloat(data.transaction.total_amount);
            } else {
                // Received money - add amount
                this.currentBalance += parseFloat(data.transaction.amount);
            }

            // Add transaction to the beginning of the list
            this.transactions.unshift(data.transaction);

            // Show notification
            this.showRealTimeNotification(data);
        },
        showRealTimeNotification(data) {
            const type = data.transaction.sender_id === this.user.id ? 'sent' : 'received';
            const amount = parseFloat(data.transaction.amount).toFixed(2);
            const name = type === 'sent' 
                ? data.transaction.receiver.name 
                : data.transaction.sender.name;
            
            const message = type === 'sent' 
                ? `You sent $${amount} to ${name}`
                : `You received $${amount} from ${name}`;
            
            this.showNotification(message, 'info');
        },
        showNotification(message, type = 'success') {
            this.notification = {
                show: true,
                message,
                type
            };
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                this.notification.show = false;
            }, 5000);
        },
        handleAuthError() {
            this.user = null;
            localStorage.removeItem('authToken');
            delete axios.defaults.headers.common['Authorization'];
            this.showNotification('Authentication error. Please login again.', 'error');
        },
    },
};
</script>

<template>
    <div id="app">
        <!-- Notification System -->
        <div v-if="notification.show" :class="['notification', notification.type]">
            {{ notification.message }}
            <button @click="notification.show = false" class="close-btn">×</button>
        </div>

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

<style>
/* Add notification styles */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.notification.success {
    background: #27ae60;
}

.notification.error {
    background: #e74c3c;
}

.notification.info {
    background: #3498db;
}

.close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    margin-left: 10px;
}
</style>