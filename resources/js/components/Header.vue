<template>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <h1>💰 Mini Wallet</h1>
            </div>
            <div class="user-info">
                <span>Welcome, {{ user.name }}</span>
                <button @click="handleLogout" class="logout-btn">
                    Logout
                </button>
            </div>
        </div>
    </header>
</template>

<script>
export default {
    name: 'Header',
    props: {
        user: {
            type: Object,
            required: true,
        },
    },
    methods: {
        handleLogout() {
            this.$emit('logout');
        },
    },
};
</script>

<style scoped>
.header {
    background: white;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    padding: 0 20px;
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 70px;
}

.logo h1 {
    color: #333;
    font-size: 24px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
    color: #666;
}

.logout-btn {
    padding: 8px 16px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #c0392b;
}
</style>