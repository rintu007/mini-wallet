<template>
    <div class="balance-section">
        <div class="balance-card">
            <div class="balance-icon">💰</div>
            <div class="balance-info">
                <h3>Current Balance</h3>
                <div class="balance-amount">${{ formattedBalance }}</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'BalanceSection',
    props: {
        balance: {
            type: Number,
            required: true,
            default: 0,
        },
    },
    computed: {
        formattedBalance() {
            return this.balance.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
        },
    },
};
</script>

<style scoped>
.balance-section {
    grid-column: 1 / -1;
}

.balance-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.balance-icon {
    font-size: 48px;
}

.balance-info h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    opacity: 0.9;
    font-weight: 500;
}

.balance-amount {
    font-size: 36px;
    font-weight: 700;
    margin: 0;
}
</style>