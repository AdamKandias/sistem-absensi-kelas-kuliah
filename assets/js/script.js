// Toggle sidebar
document.addEventListener("DOMContentLoaded", () => {
  const sidebarToggle = document.querySelector(".sidebar-toggle")
  const sidebar = document.querySelector(".sidebar")
  const contentWrapper = document.querySelector(".content-wrapper")

  // Create overlay for mobile
  const overlay = document.createElement("div")
  overlay.className = "sidebar-overlay"
  document.body.appendChild(overlay)

  if (sidebarToggle && sidebar) {
    // Toggle sidebar when hamburger is clicked
    sidebarToggle.addEventListener("click", (e) => {
      e.preventDefault()
      e.stopPropagation()

      sidebar.classList.toggle("active")

      // On mobile, show overlay when sidebar is active
      if (window.innerWidth < 768) {
        if (sidebar.classList.contains("active")) {
          overlay.classList.add("active")
          document.body.style.overflow = "hidden" // Prevent scrolling
        } else {
          overlay.classList.remove("active")
          document.body.style.overflow = "" // Allow scrolling
        }
      }
    })

    // Close sidebar when clicking on overlay
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active")
      overlay.classList.remove("active")
      document.body.style.overflow = "" // Allow scrolling
    })
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener("click", (event) => {
    if (window.innerWidth < 768 && sidebar && sidebar.classList.contains("active")) {
      if (!sidebar.contains(event.target) && event.target !== sidebarToggle) {
        sidebar.classList.remove("active")
        overlay.classList.remove("active")
        document.body.style.overflow = "" // Allow scrolling
      }
    }
  })

  // Close sidebar when clicking on a menu item on mobile
  const sidebarLinks = document.querySelectorAll(".sidebar-menu a")
  if (sidebarLinks.length > 0 && window.innerWidth < 768) {
    sidebarLinks.forEach((link) => {
      link.addEventListener("click", () => {
        if (window.innerWidth < 768) {
          sidebar.classList.remove("active")
          overlay.classList.remove("active")
          document.body.style.overflow = "" // Allow scrolling
        }
      })
    })
  }

  // Inisialisasi datepicker jika ada
  const datepickers = document.querySelectorAll(".datepicker")
  if (datepickers.length > 0) {
    datepickers.forEach((datepicker) => {
      datepicker.addEventListener("focus", function () {
        this.type = "date"
      })
      datepicker.addEventListener("blur", function () {
        if (!this.value) {
          this.type = "text"
        }
      })
    })
  }

  // Filter tabel
  const filterInput = document.getElementById("filterInput")
  if (filterInput) {
    filterInput.addEventListener("keyup", function () {
      const filterValue = this.value.toLowerCase()
      const table = document.querySelector(".table")
      const rows = table.querySelectorAll("tbody tr")

      rows.forEach((row) => {
        const text = row.textContent.toLowerCase()
        if (text.indexOf(filterValue) > -1) {
          row.style.display = ""
        } else {
          row.style.display = "none"
        }
      })
    })
  }

  // Konfirmasi sebelum menghapus
  const deleteButtons = document.querySelectorAll(".btn-delete")
  if (deleteButtons.length > 0) {
    deleteButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        if (!confirm("Apakah Anda yakin ingin menghapus data ini?")) {
          e.preventDefault()
        }
      })
    })
  }

  // Fix table responsiveness
  const tables = document.querySelectorAll("table")
  tables.forEach((table) => {
    if (!table.parentElement.classList.contains("table-responsive")) {
      const wrapper = document.createElement("div")
      wrapper.classList.add("table-responsive")
      table.parentNode.insertBefore(wrapper, table)
      wrapper.appendChild(table)
    }
  })

  // Handle window resize
  window.addEventListener("resize", () => {
    if (window.innerWidth >= 768) {
      // On desktop, ensure sidebar is visible and overlay is hidden
      overlay.classList.remove("active")
      document.body.style.overflow = ""
    }
  })
})

// Fungsi untuk filter tanggal pada daftar kehadiran
function filterTanggal() {
  const tanggalFilter = document.getElementById("tanggal_filter").value
  const url = new URL(window.location.href)

  if (tanggalFilter) {
    url.searchParams.set("tanggal", tanggalFilter)
  } else {
    url.searchParams.delete("tanggal")
  }

  window.location.href = url.toString()
}

// Fungsi untuk print halaman
function printHalaman() {
  window.print()
}
