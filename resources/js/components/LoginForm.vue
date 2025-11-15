<template>
    <div class="login-form">
        <div class="login-card">
            <h2>Mini Wallet</h2>
            <p class="subtitle">Sign in to your account</p>
            
            <form @submit.prevent="handleSubmit">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        placeholder="Enter your email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        placeholder="Enter your password"
                    >
                </div>
                
                <button type="submit" :disabled="loading" class="login-btn">
                    {{ loading ? 'Signing in...' : 'Sign in' }}
                </button>
                
                <div v-if="error" class="error-message">
                    {{ error }}
                </div>
            </form>
            
            <div class="demo-accounts">
                <h4>Demo Accounts:</h4>
                <p>john@example.com / password</p>
                <p>jane@example.com / password</p>
                <p>bob@example.com / password</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'LoginForm',
    data() {
        return {
            form: {
                email: '',
                password: '',
            },
            loading: false,
            error: '',
        };
    },
    methods: {
        async handleSubmit() {
            this.loading = true;
            this.error = '';
            
            try {
                await this.$emit('login', this.form);
            } catch (error) {
                this.error = error.message;
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>

<style scoped>
.login-form {
    width: 100%;
    max-width: 400px;
    padding: 20px;
}

.login-card {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

h2 {
    text-align: center;
    color: #333;
    margin-bottom: 8px;
    font-size: 28px;
}

.subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 30px;
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

input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
}

input:focus {
    outline: none;
    border-color: #667eea;
}

.login-btn {
    width: 100%;
    padding: 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.3s;
}

.login-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.login-btn:hover:not(:disabled) {
    opacity: 0.9;
}

.error-message {
    margin-top: 15px;
    padding: 10px;
    background: #fee;
    border: 1px solid #fcc;
    border-radius: 6px;
    color: #c33;
    text-align: center;
}

.demo-accounts {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.demo-accounts h4 {
    margin-bottom: 10px;
    color: #333;
}

.demo-accounts p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}
</style>