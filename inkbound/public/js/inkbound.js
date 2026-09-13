(function () {
	const app = window.inkboundApp || {};

	const headers = function () {
		return {
			"Content-Type": "application/json",
			"X-WP-Nonce": app.nonce || ""
		};
	};

	const post = async function (path, body) {
		if (!app.rest) return null;
		try {
			const res = await fetch(app.rest + path, {
				method: "POST",
				headers: headers(),
				credentials: "same-origin",
				body: JSON.stringify(body || {})
			});
			return await res.json();
		} catch (err) {
			return null;
		}
	};

	document.querySelectorAll(".js-follow").forEach(function (btn) {
		btn.addEventListener("click", async function () {
			if (!app.loggedIn) return;
			const data = await post("follow", { story_id: Number(btn.dataset.story) });
			if (!data) return;
			btn.setAttribute("aria-pressed", data.following ? "true" : "false");
			btn.textContent = data.following ? (app.i18n.followed || "Following") : (app.i18n.follow || "Follow");
			document.querySelectorAll(".js-follow-count").forEach(function (el) {
				el.textContent = data.count;
			});
		});
	});

	const reader = document.querySelector(".ink-reader");
	if (!reader) return;

	const storeKey = "inkbound-reader";
	const saved = JSON.parse(localStorage.getItem(storeKey) || "{}");
	const apply = function () {
		reader.dataset.theme = saved.theme || "paper";
		reader.dataset.size = saved.size || "md";
		reader.dataset.width = saved.width || "narrow";
		document.querySelectorAll(".js-theme [data-theme]").forEach(function (b) {
			b.classList.toggle("is-on", b.dataset.theme === reader.dataset.theme);
		});
		document.querySelectorAll(".js-size [data-size]").forEach(function (b) {
			b.classList.toggle("is-on", b.dataset.size === reader.dataset.size);
		});
		document.querySelectorAll(".js-width [data-width]").forEach(function (b) {
			b.classList.toggle("is-on", b.dataset.width === reader.dataset.width);
		});
	};
	apply();

	const persist = function () {
		localStorage.setItem(storeKey, JSON.stringify(saved));
		apply();
	};

	document.querySelectorAll(".js-theme [data-theme]").forEach(function (b) {
		b.addEventListener("click", function () {
			saved.theme = b.dataset.theme;
			persist();
		});
	});
	document.querySelectorAll(".js-size [data-size]").forEach(function (b) {
		b.addEventListener("click", function () {
			saved.size = b.dataset.size;
			persist();
		});
	});
	document.querySelectorAll(".js-width [data-width]").forEach(function (b) {
		b.addEventListener("click", function () {
			saved.width = b.dataset.width;
			persist();
		});
	});

	const settingsBtn = document.querySelector(".js-settings");
	const panel = document.querySelector(".ink-settings");
	if (settingsBtn && panel) {
		settingsBtn.addEventListener("click", function () {
			const open = panel.hasAttribute("hidden");
			if (open) panel.removeAttribute("hidden");
			else panel.setAttribute("hidden", "");
			settingsBtn.setAttribute("aria-expanded", open ? "true" : "false");
		});
	}

	const bar = document.querySelector(".js-read-bar");
	const prose = document.querySelector(".js-prose");
	let lastSent = 0;
	const tick = function () {
		if (!prose || !bar) return 0;
		const rect = prose.getBoundingClientRect();
		const total = prose.offsetHeight - window.innerHeight;
		const scrolled = Math.min(1, Math.max(0, (-rect.top) / Math.max(total, 1)));
		bar.style.width = (scrolled * 100) + "%";
		const percent = Math.round(scrolled * 100);
		if (app.storyId && app.chapterId && percent >= lastSent + 8) {
			lastSent = percent;
			post("progress", {
				story_id: app.storyId,
				chapter_id: app.chapterId,
				percent: percent
			});
		}
		return percent;
	};
	window.addEventListener("scroll", tick, { passive: true });
	tick();
	window.addEventListener("beforeunload", function () {
		post("progress", {
			story_id: app.storyId,
			chapter_id: app.chapterId,
			percent: Math.max(lastSent, 5)
		});
	});

	window.addEventListener("keydown", function (event) {
		if (event.target && /input|textarea|select/i.test(event.target.tagName)) return;
		if ((event.key === "ArrowRight" || event.key === "j") && app.nextUrl) {
			window.location.href = app.nextUrl;
		}
		if ((event.key === "ArrowLeft" || event.key === "k") && app.prevUrl) {
			window.location.href = app.prevUrl;
		}
	});
})();
