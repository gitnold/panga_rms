const issueModal = document.getElementById('issue-modal');
const modalTitle = document.getElementById('modal-title');
const issueTypeSelect = document.getElementById('issue_type');
const openModalButtons = document.querySelectorAll('.action-btn');
const closeModalButton = document.getElementById('close-modal');

openModalButtons.forEach(button => {
    button.addEventListener('click', () => {
        const issueType = button.getAttribute('data-type');
        if (issueType) {
            issueTypeSelect.value = issueType;
            modalTitle.textContent = 'New ' + issueType.charAt(0).toUpperCase() + issueType.slice(1);
        }
        issueModal.style.display = 'flex';
    });
});

function closeModal() {
    issueModal.style.display = 'none';
}

closeModalButton.addEventListener('click', closeModal);

issueModal.addEventListener('click', (event) => {
    if (event.target === issueModal) {
        closeModal();
    }
});
