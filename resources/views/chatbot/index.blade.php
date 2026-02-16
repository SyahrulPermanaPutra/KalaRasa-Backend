@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Recipe Chatbot</h4>
                </div>

                <div class="card-body">
                    <!-- Chat Messages -->
                    <div id="chat-messages" style="height: 400px; overflow-y: auto; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <div class="text-center text-muted">
                            <p>Halo! Aku Recipe Bot. Mau masak apa hari ini? 🍳</p>
                        </div>
                    </div>

                    <!-- Suggestions -->
                    <div id="suggestions" class="mb-3"></div>

                    <!-- Input Form -->
                    <form id="chat-form">
                        <div class="input-group">
                            <input 
                                type="text" 
                                id="message-input" 
                                class="form-control" 
                                placeholder="Ketik pesan..."
                                autocomplete="off"
                            >
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>

                    <!-- Actions -->
                    <div class="mt-3">
                        <button id="clear-btn" class="btn btn-sm btn-outline-danger">
                            Clear Conversation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Recipes Display -->
            <div id="recipes-container" class="mt-4"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const chatMessages = document.getElementById('chat-messages');
    const suggestionsDiv = document.getElementById('suggestions');
    const recipesContainer = document.getElementById('recipes-container');
    const clearBtn = document.getElementById('clear-btn');

    // Send message
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;

        // Add user message
        addMessage('user', message);
        messageInput.value = '';

        // Show loading
        addMessage('bot', 'Thinking...', true);

        try {
            const response = await fetch('{{ route("chatbot.message") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ message })
            });

            const data = await response.json();

            // Remove loading
            chatMessages.querySelector('.loading')?.remove();

            // Add bot response
            addMessage('bot', data.response);

            // Show suggestions
            if (data.suggestions && data.suggestions.length > 0) {
                showSuggestions(data.suggestions);
            }

            // Show recipes
            if (data.recipes && data.recipes.length > 0) {
                showRecipes(data.recipes);
            }

        } catch (error) {
            console.error('Error:', error);
            addMessage('bot', 'Maaf, terjadi kesalahan. Coba lagi.');
        }
    });

    // Clear conversation
    clearBtn.addEventListener('click', async function() {
        if (!confirm('Clear conversation history?')) return;

        try {
            const response = await fetch('{{ route("chatbot.clear") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();
            
            if (data.success) {
                chatMessages.innerHTML = '<div class="text-center text-muted"><p>Conversation cleared. Mau mulai lagi?</p></div>';
                suggestionsDiv.innerHTML = '';
                recipesContainer.innerHTML = '';
            }
        } catch (error) {
            console.error('Error:', error);
        }
    });

    // Helper functions
    function addMessage(sender, text, isLoading = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `mb-2 ${sender === 'user' ? 'text-end' : 'text-start'} ${isLoading ? 'loading' : ''}`;
        
        const bubble = document.createElement('div');
        bubble.className = `d-inline-block px-3 py-2 rounded ${sender === 'user' ? 'bg-primary text-white' : 'bg-light'}`;
        bubble.style.maxWidth = '80%';
        bubble.innerHTML = text.replace(/\n/g, '<br>');
        
        messageDiv.appendChild(bubble);
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showSuggestions(suggestions) {
        suggestionsDiv.innerHTML = '<small class="text-muted">Suggestions:</small><br>';
        suggestions.slice(0, 3).forEach(suggestion => {
            const btn = document.createElement('button');
            btn.className = 'btn btn-sm btn-outline-secondary me-2 mb-2';
            btn.textContent = suggestion;
            btn.onclick = () => {
                messageInput.value = suggestion;
                chatForm.dispatchEvent(new Event('submit'));
            };
            suggestionsDiv.appendChild(btn);
        });
    }

    function showRecipes(recipes) {
        recipesContainer.innerHTML = '<h5>Recipe yang Cocok:</h5>';
        
        recipes.slice(0, 3).forEach((match, index) => {
            const recipe = match.recipe;
            const card = document.createElement('div');
            card.className = 'card mb-3';
            card.innerHTML = `
                <div class="card-body">
                    <h6>${index + 1}. ${recipe.nama}</h6>
                    <p class="small mb-1">
                        ⏱ ${recipe.waktu_masak} menit | 
                        👨‍🍳 ${recipe.tingkat_kesulitan} | 
                        🔥 ${recipe.kalori_per_porsi} kal
                    </p>
                    <div class="progress mb-2" style="height: 5px;">
                        <div class="progress-bar" style="width: ${match.score}%"></div>
                    </div>
                    <small class="text-muted">Match: ${match.score.toFixed(0)}/100</small>
                </div>
            `;
            recipesContainer.appendChild(card);
        });
    }
});
</script>
@endsection