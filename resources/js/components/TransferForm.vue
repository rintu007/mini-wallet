<template>
    <div class="transfer-form">
        <div class="card">
            <h3>Transfer Money</h3>
            
            <form @submit.prevent="handleSubmit">
                <div class="form-group">
                    <label for="receiver">Recipient</label>
                    <select
                        id="receiver"
                        v-model="form.receiver_id"
                        required
                        class="select-input"
                    >
                        <option value="">Select a recipient</option>
                        <option 
                            v-for="user in filteredUsers" 
                            :key="user.id" 
                            :value="user.id"
                        >
                            {{ user.name }} ({{ user.email }})
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount">Amount</label>
                    <div class="amount-input-container">
                        <span class="currency-symbol">$</span>
                        <input
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="maxAmount"
                            required
                            placeholder="0.00"
                            class="amount-input"
                        >
                    </div>
                    <div class="amount-info">
                        <small>Available: ${{ formattedBalance }}</small>
                        <small>Commission: 1.5%</small>
                    </div>
                </div>
                
                <div class="transfer-summary" v-if="form.amount > 0">
                    <div class="summary-item">
                        <span>Transfer Amount:</span>
                        <span>${{ parseFloat(form.amount || 0).toFixed(2) }}</span>
                    </div>
                    <div class="summary-item">
                        <span>Commission Fee:</span>
                        <span>${{ commissionFee.toFixed(2) }}</span>
                    </div>
                    <div class="summary-item total">
                        <span>Total Deduction:</span>
                        <span>${{ totalDeduction.toFixed(2) }}</span>
                    </div>
                </div>
                
                <button 
                    type="submit" 
                    :disabled="!isFormValid || loading" 
                    class="transfer-btn"
                >
                    {{ loading ? 'Processing...' : 'Send Money' }}
                </button>
                
                <div v-if="error" class="error-message">
                    {{ error }}
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TransferForm',
    props: {
        users: {
            type: Array,
            default: () => [],
        },
        loading: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            form: {
                receiver_id: '',
                amount: '',
            },
            error: '',
            currentBalance: 10000, // This should come from parent
        };
    },
    computed: {
        filteredUsers() {
            return this.users.filter(user => user.id !== this.$parent.user?.id);
        },
        formattedBalance() {
            return this.currentBalance.toFixed(2);
        },
        commissionFee() {
            return (parseFloat(this.form.amount || 0) * 0.015);
        },
        totalDeduction() {
            return parseFloat(this.form.amount || 0) + this.commissionFee;
        },
        maxAmount() {
            return this.currentBalance / 1.015; // Account for commission
        },
        isFormValid() {
            return this.form.receiver_id && 
                   this.form.amount > 0 && 
                   this.totalDeduction <= this.currentBalance;
        },
    },
    methods: {
        async handleSubmit() {
            this.error = '';
            
            if (this.totalDeduction > this.currentBalance) {
                this.error = 'Insufficient balance including commission fee';
                return;
            }
            
            try {
                await this.$emit('transfer', this.form);
                // Clear form on success
                this.form.receiver_id = '';
                this.form.amount = '';
            } catch (error) {
                    this.error = error.message;
                }
            },
    },
    mounted() {
        // Get current balance from parent
        this.currentBalance = this.$parent.currentBalance;
    },
    watch: {
        '$parent.currentBalance': {
            handler(newBalance) {
                this.currentBalance = newBalance;
            },
            immediate: true,
        },
    },
};
</script>

<style scoped>
.transfer-form {
    grid-column: 1;
}

.card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 20px;
}

.form-group {
    margin-bottom: 20px;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.select-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    background: white;
}

.amount-input-container {
    position: relative;
    display: flex;
    align-items: center;
}

.currency-symbol {
    position: absolute;
    left: 16px;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    z-index: 1;
}

.amount-input {
    width: 100%;
    padding: 12px 16px 12px 30px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
}

.amount-input:focus {
    outline: none;
    border-color: #667eea;
}

.amount-info {
    display: flex;
    justify-content: space-between;
    margin-top: 5px;
    font-size: 12px;
    color: #666;
}

.transfer-summary {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.summary-item.total {
    font-weight: 600;
    border-top: 1px solid #ddd;
    padding-top: 8px;
    margin-top: 8px;
    font-size: 16px;
    color: #333;
}

.transfer-btn {
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.3s;
}

.transfer-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.transfer-btn:hover:not(:disabled) {
    opacity: 0.9;
}

.error-message {
    margin-top: 15px;
    padding: 12px;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 6px;
    color: #c33;
    text-align: center;
    font-size: 14px;
}
</style>