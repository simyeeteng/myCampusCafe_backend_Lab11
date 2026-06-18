// ============================================================
// UPDATED: Frontend State Management with API Integration
// API_CONFIG, getToken, setToken, etc. are defined in js/api.js
// ============================================================

// Shared reactive store
const store = Vue.reactive({
    foods: [],        // Will be loaded from backend API
    orders: [],       // Orders from backend (for future feature)
    message: '',
    isLoggedIn: !!getToken(),
    login: {
        username: '',
        password: ''
    },
    newMenu: {
        menu_name: '',
        category: '',
        price: '',
        availability: 'Available'
    }
});

// ============================================================
// API Functions - Using REAL Backend (Lab 10)
// ============================================================

// PUBLIC: Fetch menu from PHP Slim backend (no token needed)
async function fetchMenu() {
    try {
        const response = await fetch(API_CONFIG.BASE_URL + '/menu');
        if (response.ok) {
            store.foods = await response.json();
            store.message = '';
        } else {
            store.message = 'Failed to load menu.';
        }
    } catch (error) {
        store.message = 'Cannot connect to server.';
        console.error(error);
    }
}

// PROTECTED: Add new menu item (requires token)
async function addMenuItem(menuData) {
    try {
        const response = await fetch(API_CONFIG.BASE_URL + '/menu', {
            method: 'POST',
            headers: authHeaders(),
            body: JSON.stringify(menuData)
        });
        const result = await response.json();
        
        if (response.ok) {
            store.message = 'Menu item added successfully!';
            await fetchMenu(); // Refresh the list
            return true;
        } else {
            store.message = result.message || 'Add failed.';
            if (response.status === 401) {
                store.isLoggedIn = false;
                clearToken();
            }
            return false;
        }
    } catch (error) {
        store.message = 'Server connection error.';
        console.error(error);
        return false;
    }
}

// PROTECTED: Update menu item (requires token)
async function updateMenuItem(item) {
    try {
        const response = await fetch(API_CONFIG.BASE_URL + '/menu/' + item.menu_id, {
            method: 'PUT',
            headers: authHeaders(),
            body: JSON.stringify(item)
        });
        const result = await response.json();
        
        if (response.ok) {
            store.message = 'Menu updated successfully!';
            await fetchMenu();
            return true;
        } else {
            store.message = result.message || 'Update failed.';
            if (response.status === 401) {
                store.isLoggedIn = false;
                clearToken();
            }
            return false;
        }
    } catch (error) {
        store.message = 'Unable to update menu.';
        console.error(error);
        return false;
    }
}

// PROTECTED: Delete menu item (requires token)
async function deleteMenuItem(id) {
    try {
        const response = await fetch(API_CONFIG.BASE_URL + '/menu/' + id, {
            method: 'DELETE',
            headers: authHeaders()
        });
        const result = await response.json();
        
        if (response.ok) {
            store.message = 'Menu deleted successfully!';
            await fetchMenu();
            return true;
        } else {
            store.message = result.message || 'Delete failed.';
            if (response.status === 401) {
                store.isLoggedIn = false;
                clearToken();
            }
            return false;
        }
    } catch (error) {
        store.message = 'Unable to delete menu.';
        console.error(error);
        return false;
    }
}

// PUBLIC: Login - Get JWT token
async function loginStaff(username, password) {
    try {
        const response = await fetch(API_CONFIG.BASE_URL + '/login', {
            method: 'POST',
            headers: publicHeaders(),
            body: JSON.stringify({ username, password })
        });
        const result = await response.json();
        
        if (response.ok && result.token) {
            setToken(result.token);
            store.isLoggedIn = true;
            store.message = 'Login successful!';
            return true;
        } else {
            store.message = result.message || 'Invalid login.';
            return false;
        }
    } catch (error) {
        store.message = 'Unable to connect to server.';
        console.error(error);
        return false;
    }
}

// PUBLIC: Logout
function logout() {
    clearToken();
    store.isLoggedIn = false;
    store.message = 'Logged out successfully.';
}

// ============================================================
// Home Route Component
// ============================================================
const HomePage = {
    template: `
        <div>
            <h2>Welcome to MyCampus Café</h2>
            <p>This SPA allows navigation between pages without reloading the browser.</p>
            <p v-if="store.isLoggedIn" style="color: green;">✅ You are logged in as admin</p>
            <p v-else style="color: red;">❌ Please login to manage menu items</p>
        </div>
    `
};

// ============================================================
// Login Component
// ============================================================
const LoginPage = {
    data() {
        return {
            username: '',
            password: ''
        };
    },
    computed: {
        message() {
            return store.message;
        },
        isLoggedIn() {
            return store.isLoggedIn;
        }
    },
    methods: {
        async login() {
            const success = await loginStaff(this.username, this.password);
            if (success) {
                this.username = '';
                this.password = '';
                this.$router.push('/manage-menu');
            }
        },
        logout() {
            logout();
        }
    },
    template: `
        <div>
            <h2>Staff Login</h2>
            <div v-if="!isLoggedIn">
                <label>Username:</label>
                <input type="text" v-model="username" placeholder="admin"><br><br>
                <label>Password:</label>
                <input type="password" v-model="password" placeholder="admin123"><br><br>
                <button @click="login">Login</button>
            </div>
            <div v-else>
                <p style="color: green;">✅ You are logged in</p>
                <button @click="logout">Logout</button>
            </div>
            <p v-if="message">{{ message }}</p>
        </div>
    `
};

// ============================================================
// Manage Menu Component (CRUD - requires login)
// ============================================================
const ManageMenuPage = {
    data() {
        return {
            editingItem: null,
            newItem: {
                menu_name: '',
                category: '',
                price: '',
                availability: 'Available'
            }
        };
    },
    computed: {
        foods() {
            return store.foods;
        },
        message() {
            return store.message;
        },
        isLoggedIn() {
            return store.isLoggedIn;
        }
    },
    methods: {
        async addItem() {
            if (!this.isLoggedIn) {
                store.message = 'Please login first!';
                return;
            }
            if (!this.newItem.menu_name || !this.newItem.category || !this.newItem.price) {
                store.message = 'Please fill in all fields.';
                return;
            }
            await addMenuItem(this.newItem);
            this.newItem = { menu_name: '', category: '', price: '', availability: 'Available' };
        },
        
        startEdit(item) {
            this.editingItem = { ...item };
        },
        
        cancelEdit() {
            this.editingItem = null;
        },
        
        async saveEdit() {
            if (this.editingItem) {
                await updateMenuItem(this.editingItem);
                this.editingItem = null;
            }
        },
        
        async deleteItem(id) {
            if (confirm('Are you sure you want to delete this menu item?')) {
                await deleteMenuItem(id);
            }
        },
        
        async loadMenu() {
            await fetchMenu();
        }
    },
    async mounted() {
        await fetchMenu();
    },
    template: `
        <div>
            <h2>Manage Menu Items</h2>
            
            <div v-if="!isLoggedIn" style="color: red; border: 1px solid red; padding: 10px;">
                ⚠️ You are not logged in. Please <router-link to="/login">Login</router-link> to manage menu.
            </div>
            
            <div v-else>
                <h3>Add New Menu Item</h3>
                <input v-model="newItem.menu_name" placeholder="Menu Name"><br>
                <input v-model="newItem.category" placeholder="Category"><br>
                <input v-model="newItem.price" placeholder="Price (RM)"><br>
                <select v-model="newItem.availability">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select><br>
                <button @click="addItem">Add Menu Item</button>
                <hr>
            </div>
            
            <h3>Menu List</h3>
            <table border="1" cellpadding="8" v-if="foods.length > 0">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (RM)</th>
                    <th>Availability</th>
                    <th v-if="isLoggedIn">Actions</th>
                </tr>
                <tr v-for="item in foods" :key="item.menu_id">
                    <td>{{ item.menu_id }}</td>
                    <td v-if="editingItem && editingItem.menu_id === item.menu_id">
                        <input v-model="editingItem.menu_name">
                    </td>
                    <td v-else>{{ item.menu_name }}</td>
                    
                    <td v-if="editingItem && editingItem.menu_id === item.menu_id">
                        <input v-model="editingItem.category">
                    </td>
                    <td v-else>{{ item.category }}</td>
                    
                    <td v-if="editingItem && editingItem.menu_id === item.menu_id">
                        <input v-model="editingItem.price">
                    </td>
                    <td v-else>{{ item.price }}</td>
                    
                    <td v-if="editingItem && editingItem.menu_id === item.menu_id">
                        <select v-model="editingItem.availability">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </td>
                    <td v-else>{{ item.availability }}</td>
                    
                    <td v-if="isLoggedIn">
                        <div v-if="editingItem && editingItem.menu_id === item.menu_id">
                            <button @click="saveEdit">Save</button>
                            <button @click="cancelEdit">Cancel</button>
                        </div>
                        <div v-else>
                            <button @click="startEdit(item)">Edit</button>
                            <button @click="deleteItem(item.menu_id)">Delete</button>
                        </div>
                    </td>
                </tr>
            </table>
            <p v-else>No menu items available.</p>
            
            <p v-if="message">{{ message }}</p>
        </div>
    `
};

// ============================================================
// Route Definitions
// ============================================================
const routes = [
    { path: '/', component: HomePage },
    { path: '/login', component: LoginPage },
    { path: '/manage-menu', component: ManageMenuPage }
];

// ============================================================
// Router Setup
// ============================================================
const router = VueRouter.createRouter({
    history: VueRouter.createWebHashHistory(),
    routes
});

// ============================================================
// Vue App Instance
// ============================================================
const app = Vue.createApp({
    data() {
        return {
            appTitle: 'MyCampus Café - SPA'
        };
    }
});

// Make store available in templates
app.config.globalProperties.$store = store;

app.use(router);
app.mount('#app');