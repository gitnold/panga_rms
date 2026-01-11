const issueModal = document.getElementById('issue-modal');
const modalTitle = document.getElementById('modal-title');
const issueTypeSelect = document.getElementById('issue_type');
const openModalButtons = document.querySelectorAll('.action-btn');
const closeModalButton = document.getElementById('close-modal');

const openModalButton = document.querySelector('.file-issue-btn');

if (openModalButton) {
    openModalButton.addEventListener('click', () => {
        modalTitle.textContent = 'New Issue';
        issueModal.style.display = 'flex';
    });
}

function closeModal() {
    issueModal.style.display = 'none';
}

closeModalButton.addEventListener('click', closeModal);

issueModal.addEventListener('click', (event) => {
    if (event.target === issueModal) {
        closeModal();
    }
});
