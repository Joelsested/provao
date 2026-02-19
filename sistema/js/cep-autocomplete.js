(() => {
  const cepCache = new Map();
  const timers = new WeakMap();

  function digitsOnly(value) {
    return String(value || "").replace(/\D/g, "");
  }

  function findField(container, names) {
    for (const name of names) {
      if (!name) continue;
      const byId = document.getElementById(name);
      if (byId) return byId;
      if (container) {
        const byName = container.querySelector(`[name="${name}"]`);
        if (byName) return byName;
      }
    }
    return null;
  }

  function candidateNames(base, suffix) {
    const list = [];
    if (suffix) list.push(base + suffix);
    if (base === "cidade" && suffix === "_usu") list.push("cidadeo_usu");
    list.push(base, base + "_usu", base + "_usuario");
    return Array.from(new Set(list));
  }

  function detectSuffix(input) {
    const id = input.id || "";
    const name = input.name || "";
    if (id.endsWith("_usu") || name.endsWith("_usu")) return "_usu";
    if (id.endsWith("_usuario") || name.endsWith("_usuario")) return "_usuario";
    return "";
  }

  function fillAddressFields(input, data) {
    const container = input.form || input.closest("form") || document;
    const suffix = detectSuffix(input);

    const enderecoField = findField(container, candidateNames("endereco", suffix));
    const bairroField = findField(container, candidateNames("bairro", suffix));
    const cidadeField = findField(container, candidateNames("cidade", suffix));
    const estadoField = findField(container, candidateNames("estado", suffix));

    if (enderecoField && data.logradouro) enderecoField.value = data.logradouro;
    if (bairroField && data.bairro) bairroField.value = data.bairro;
    if (cidadeField && data.localidade) cidadeField.value = data.localidade;
    if (estadoField && data.uf) estadoField.value = data.uf;
  }

  async function loadCep(input, cep) {
    if (cepCache.has(cep)) {
      fillAddressFields(input, cepCache.get(cep));
      return;
    }

    try {
      const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      if (!response.ok) return;
      const data = await response.json();
      if (data && !data.erro) {
        cepCache.set(cep, data);
        fillAddressFields(input, data);
      }
    } catch (_) {
      // Falha de rede: mantem campos para preenchimento manual.
    }
  }

  function scheduleCepLookup(input) {
    const cep = digitsOnly(input.value);
    if (cep.length !== 8) return;

    const currentTimer = timers.get(input);
    if (currentTimer) clearTimeout(currentTimer);

    const timer = setTimeout(() => {
      loadCep(input, cep);
    }, 250);
    timers.set(input, timer);
  }

  document.addEventListener(
    "input",
    (event) => {
      const target = event.target;
      if (!(target instanceof HTMLInputElement)) return;
      const idOrName = `${target.id || ""} ${target.name || ""}`.toLowerCase();
      if (!idOrName.includes("cep")) return;
      scheduleCepLookup(target);
    },
    true
  );

  document.addEventListener(
    "blur",
    (event) => {
      const target = event.target;
      if (!(target instanceof HTMLInputElement)) return;
      const idOrName = `${target.id || ""} ${target.name || ""}`.toLowerCase();
      if (!idOrName.includes("cep")) return;
      scheduleCepLookup(target);
    },
    true
  );
})();

