<template>
    <div class="transaction-history">
        <div class="card">
            <h3>Transaction History</h3>
            
            <div v-if="loading" class="loading">
                Loading transactions...
            </div>
            
            <div v-else-if="transactions.length === 0" class="empty-state">
                No transactions yet
            </div>
            
            <div v-else class="transactions-list">
                <div 
                    v-for="transaction in transactions" 
                    :key="transaction.id"
                    class="transaction-item"
                    :class="{
                        'sent': transaction.sender_id === $parent.user?.id,
                        'received': transaction.receiver_id === $parent.user?.id
                    }"
                >
                    <div class="transaction-icon">
                        <span v-if="transaction.sender_id === $parent.user?.id">➚</span>
                        <span v-else>➘</span>
                    </div>
                    
                    <div class="transaction-details">
                        <div class="transaction-party">
                            <template v-if="transaction.sender_id === $parent.user?.id">
                                To: {{ transaction.receiver.name }}
                            </template>
                            <template v-else>
                                From: {{ transaction.sender.name }}
                            </template>
                        </div>
                        <div class="transaction-date">
                            {{ formatDate(transaction.created_at) }}
                        </div>
                    </div>
                    
                    <div class="transaction-amount" :class="{
                        'sent': transaction.sender_id === $parent.user?.id,
                        'received': transaction.receiver_id === $parent.user?.id
                    }">
                        <template v-if="transaction.sender_id === $parent.user?.id">
                            -${{ parseFloat(transaction.total_amount).toFixed(2) }}
                        </template>
                        <template v-else>
                            +${{ parseFloat(transaction.amount).toFixed(2) }}
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TransactionHistory',
    props: {
        transactions: {
            type: Array,
            default: () => [],
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    methods: {
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            });
        },
    },
};
</script>

<style scoped>
.transaction-history {
    grid-column: 2;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    height: 500px;
    display: flex;
    flex-direction: column;
}

h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 20px;
}

.loading, .empty-state {
    text-align: center;
    color: #666;
    padding: 40px 0;
    font-style: italic;
}

.transactions-list {
    flex: 1;
    overflow-y: auto;
}

.transaction-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f0;
    gap: 15px;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.transaction-item.sent .transaction-icon {
    background: #fff0f0;
    color: #e74c3c;
}

.transaction-item.received .transaction-icon {
    background: #f0fff0;
    color: #27ae60;
}

.transaction-details {
    flex: 1;
}

.transaction-party {
    font-weight: 500;
    color: #333;
    margin-bottom: 4px;
}

.transaction-date {
    font-size: 12px;
    color: #666;
}

.transaction-amount {
    font-weight: 600;
    font-size: 16px;
}

.transaction-amount.sent {
    color: #e74c3c;
}

.transaction-amount.received {
    color: #27ae60;
}

/* Scrollbar styling */
.transactions-list::-webkit-scrollbar {
    width: 6px;
}

.transactions-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.transactions-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.transactions-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

@media (max-width: 768px) {
    .transaction-history {
        grid-column: 1;
    }
    
    .card {
        height: auto;
        max-height: 400px;
    }
}
</style>