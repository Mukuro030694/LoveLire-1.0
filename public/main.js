document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('.login-box form');
    const registerForm = document.querySelector('.register-box form');
    const addBookForm = document.getElementById('addBookForm');
    const saveChangesBtn = document.getElementById('saveChangesBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const modal = document.getElementById('bookModal');
    const modalBookIdInput = document.getElementById('modalBookId');
    const bsModal = modal ? new bootstrap.Modal(modal) : null;

    // login
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;

            const response = await fetch('/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ username, password })
            });

            if (response.ok) {
                window.location.href = '/library';
            } else {
                alert('Erreur de connexion');
            }
        });
    }

    // register
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const username = document.getElementById('reg_username').value.trim();
            const password = document.getElementById('reg_password').value.trim();

            if (!username || !password) {
                alert('Veuillez remplir tous les champs.');
                return;
            }

            try {
                const registerResponse = await fetch('/auth/register', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password, roles: ['ROLE_USER'] }),
                });

                if (registerResponse.ok) {
                    alert('Création du compte réussie !');


                    const loginResponse = await fetch('/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, password }),
                        credentials: 'include',
                    });

                    if (loginResponse.ok) {
                        window.location.href = '/library';
                        alert('Erreur de connexion après inscription, veuillez vous connecter manuellement.');
                        window.location.href = '/auth/auth_page';
                    }
                } else {
                    const errorData = await registerResponse.json();
                    alert('Erreur : ' + (errorData.error || 'Erreur inconnue'));
                }
            } catch (error) {
                alert('Erreur réseau : ' + error.message);
            }
        });
    }


    //TODO
    // library protection
    if (window.location.pathname === '/library') {
        fetch('/api/check', { credentials: 'include' })
            .then(res => {
                if (res.status === 401) window.location.href = '/login';
            })
            .catch(() => window.location.href = '/login');
    }

    // add book
    if (addBookForm) {
        addBookForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const title = document.getElementById('book-title').value.trim();
            const comment = document.getElementById('book-comment').value.trim();
            const note = document.getElementById('book-note').value;
            const status = document.getElementById('book-status').value;
            const coverInput = document.getElementById('book-cover');

            if (!title) {
                alert('Le titre est obligatoire.');
                return;
            }

            const formData = new FormData();
            formData.append('title', title);
            formData.append('comment', comment);
            formData.append('note', note);
            formData.append('status', status);

            if (coverInput && coverInput.files.length > 0) {
                formData.append('coverImage', coverInput.files[0]);
            }

            try {
                const response = await fetch('/book/add', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                if (response.ok) {
                    alert('Livre ajouté avec succès !');
                    window.location.reload();
                } else {
                    const error = await response.json();
                    alert('Erreur: ' + (error.error || 'Une erreur est survenue.'));
                }
            } catch (err) {
                alert('Erreur réseau : ' + err.message);
            }
        });
    }
    // cover preview
    const coverPreview = document.getElementById('addCoverPreview');
    const coverInput = document.getElementById('book-cover');

    if (coverPreview && coverInput) {

        coverPreview.addEventListener('click', () => {
            coverInput.click();
        });


        coverInput.addEventListener('change', () => {
            if (coverInput.files && coverInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    coverPreview.src = e.target.result;
                };
                reader.readAsDataURL(coverInput.files[0]);
            }
        });
    }


    // modify book
    saveChangesBtn.addEventListener('click', () => {
        const bookId = modalBookIdInput.value;

        const formData = new FormData();
        formData.append('title', document.getElementById('modalTitle').value);
        formData.append('status', document.getElementById('modalStatus').value);
        formData.append('note', document.getElementById('modalNote').value);
        formData.append('comment', document.getElementById('modalComment').value);

        const coverFileInput = document.getElementById('modalCoverUpload');
        if (coverFileInput.files.length > 0) {
            formData.append('coverImage', coverFileInput.files[0]);
        }

        fetch(`/book/modify/${bookId}?_method=PUT`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
            .then(res => res.json())
            .then(json => {
                if (json.error) {
                    alert('Erreur : ' + json.error);
                } else {
                    alert('Livre modifié avec succès');
                    location.reload();
                }
            })
            .catch(() => alert('Erreur réseau'));
    });

    const modalCover = document.getElementById('modalCoverImage');
    const modalInput = document.getElementById('modalCoverUpload');

    if (modalCover && modalInput) {
        modalCover.addEventListener('click', () => {
            modalInput.click();
        });

        modalInput.addEventListener('change', () => {
            if (modalInput.files && modalInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    modalCover.src = e.target.result;
                };
                reader.readAsDataURL(modalInput.files[0]);
            }
        });
    }


    // card click event
    document.querySelectorAll('.card.clickable').forEach(card => {
        card.addEventListener('click', () => {
            const bookId = card.dataset.id;
            const title = card.dataset.title;
            const status = card.dataset.status;
            const note = card.dataset.note;
            const comment = card.dataset.comment;

            const coverImage = card.querySelector('img')?.getAttribute('src');

            document.getElementById('modalBookId').value = bookId;
            document.getElementById('modalTitle').value = title;
            document.getElementById('modalStatus').value = status;
            document.getElementById('modalNote').value = note;
            document.getElementById('modalComment').value = comment;

            document.getElementById('modalCoverImage').src = coverImage;

            const modal = new bootstrap.Modal(document.getElementById('bookModal'));
            modal.show();
        });


    });

    document.getElementById('modalCoverImage').addEventListener('click', () => {
        document.getElementById('modalCoverUpload').click();
    });

    document.getElementById('modalCoverImage').addEventListener('click', function () {
        document.getElementById('modalCoverUpload').click();
    });

    document.getElementById('modalCoverUpload').addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();

            reader.onload = function (e) {
                document.getElementById('modalCoverImage').src = e.target.result;
            };

            reader.readAsDataURL(file);
        }
    });


    // delete book
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            const bookId = modalBookIdInput.value;

            if (!confirm('Voulez-vous vraiment supprimer ce livre ?')) return;

            fetch(`/book/delete/${bookId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
                .then(res => res.json())
                .then(json => {
                    if (json.error) {
                        alert('Erreur : ' + json.error);
                    } else {
                        alert('Livre supprimé avec succès');
                        location.reload();
                    }
                })
                .catch(() => alert('Erreur réseau'));
        });
    }

    document.getElementById('logoutBtn').addEventListener('click', async () => {
        if (!confirm("Voulez-vous vraiment vous déconnecter ?")) return;

        try {
            const response = await fetch('/auth/auth/logout', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                window.location.href = '/';
            } else {
                alert("Échec de la déconnexion.");
            }
        } catch (err) {
            console.error("Erreur réseau pendant la déconnexion :", err);
            alert("Erreur réseau.");
        }
    });
    //Delete or list users
    document.getElementById('openUserModal').addEventListener('click', async () => {
        const usersList = document.getElementById('userList');
        usersList.innerHTML = 'Chargement...';

        document.getElementById('userModal').style.display = 'block';

        try {
            const response = await fetch('/api/user/list-users', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                usersList.innerHTML = '<li>Erreur de chargement des utilisateurs</li>';
                return;
            }

            const users = await response.json();

            if (users.length === 0) {
                usersList.innerHTML = '<li>Aucun utilisateur trouvé</li>';
                return;
            }

            usersList.innerHTML = '';

            users.forEach(user => {
                const li = document.createElement('li');
                li.classList.add('list-group-item', 'd-flex', 'justify-content-between', 'align-items-center');
                li.textContent = user.username;

                const btnDel = document.createElement('button');
                btnDel.classList.add('btn', 'btn-danger', 'btn-sm', 'ms-3');
                btnDel.textContent = 'Supprimer';

                btnDel.addEventListener('click', async () => {
                    if (!confirm(`Supprimer l'utilisateur ${user.username} ?`)) return;

                    const delResponse = await fetch(`/api/user/delete/${user.id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (delResponse.ok) {
                        li.remove();
                        alert('Utilisateur supprimé');
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                });

                li.appendChild(btnDel);
                usersList.appendChild(li);
            });

        } catch (e) {
            usersList.innerHTML = '<li>Erreur de communication avec le serveur</li>';
            console.error(e);
        }
    });






});
