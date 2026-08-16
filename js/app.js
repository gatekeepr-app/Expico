document.querySelectorAll("[data-toggle-password]").forEach((button) => {
    button.addEventListener("click", () => {
        const input = document.getElementById(button.dataset.togglePassword);

        if (!input) {
            return;
        }

        const isHidden = input.type === "password";
        input.type = isHidden ? "text" : "password";
        button.textContent = isHidden ? "Hide" : "Show";
        button.setAttribute("aria-pressed", String(isHidden));
    });
});

const sheet = document.querySelector("[data-bottom-sheet]");
const backdrop = document.querySelector("[data-sheet-backdrop]");

function setSheetOpen(isOpen) {
    if (!sheet || !backdrop) {
        return;
    }

    sheet.classList.toggle("open", isOpen);
    backdrop.classList.toggle("open", isOpen);
}

document.querySelectorAll("[data-open-sheet]").forEach((button) => {
    button.addEventListener("click", () => setSheetOpen(true));
});

backdrop?.addEventListener("click", () => setSheetOpen(false));

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        setSheetOpen(false);
        closeOpenModal();
    }
});

function setModalOpen(modal, isOpen) {
    if (!modal) {
        return;
    }

    modal.hidden = !isOpen;
    modal.classList.toggle("open", isOpen);

    if (isOpen) {
        modal.querySelector("input, select, textarea, button")?.focus();
    }
}

function closeOpenModal() {
    document.querySelectorAll("[data-modal].open").forEach((modal) => {
        setModalOpen(modal, false);
    });
}

document.querySelectorAll("[data-open-modal]").forEach((button) => {
    button.addEventListener("click", () => {
        setModalOpen(document.querySelector(`[data-modal="${button.dataset.openModal}"]`), true);
    });
});

document.querySelectorAll("[data-modal]").forEach((modal) => {
    modal.addEventListener("click", (event) => {
        if (event.target === modal || event.target.closest("[data-close-modal]")) {
            setModalOpen(modal, false);
        }
    });
});

document.querySelectorAll("[data-confirm]").forEach((element) => {
    element.addEventListener("click", (event) => {
        if (!confirm(element.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll("[data-filter]").forEach((button) => {
    button.addEventListener("click", () => {
        const filter = button.dataset.filter;
        const group = button.closest("[data-filter-group]");

        group?.querySelectorAll("[data-filter]").forEach((item) => {
            item.classList.toggle("active", item === button);
        });

        document.querySelectorAll("[data-filter-item]").forEach((item) => {
            item.hidden = filter !== "all" && item.dataset.filterItem !== filter;
        });
    });
});

const splitAmount = document.querySelector("[data-split-amount]");
const splitChecks = document.querySelectorAll("[data-split-participant]");
const splitTotal = document.querySelector("[data-split-total]");
const splitCount = document.querySelector("[data-split-count]");
const splitEach = document.querySelector("[data-split-each]");

function updateSplitPreview() {
    if (!splitAmount || !splitTotal || !splitCount || !splitEach) {
        return;
    }

    const amount = Number(splitAmount.value || 0);
    const count = Array.from(splitChecks).filter((checkbox) => checkbox.checked).length;
    const each = count > 0 ? amount / count : 0;

    splitTotal.textContent = `৳${amount.toFixed(2)}`;
    splitCount.textContent = String(count);
    splitEach.textContent = `৳${each.toFixed(2)}`;
}

splitAmount?.addEventListener("input", updateSplitPreview);
splitChecks.forEach((checkbox) => checkbox.addEventListener("change", updateSplitPreview));
updateSplitPreview();
