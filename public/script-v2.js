const url = new URL(document.currentScript.src).origin
const root = document.querySelector('[x-data]');

async function init() {
    //console.log('init')
    //console.log('fetchMessages')
    fetch(url+'/messages')
        .then(response => response.json())
        .then(data => {
            this.messages = data.messages
        })

    // Step 5 => (pré)chargement du calendrier
    //this.$watch('step', () => this.step === 5 && this.fetchCalendrier())

    this.$watch('step', () => root.scrollIntoView({ behavior: 'smooth' }))

    this.$watch('contact', () => this.contactValid =
        this.contact.nom
        && this.contact.email
        && this.contact.tel)
}

function data() {
    //console.log('data')
    return {
        loading: false,
        step: 1,
        type: null,
        boisLst: [],
        bois: 'pin_blanc',
        poigneeLst: [],
        poignee: 'poignee_moderne',
        couleurPoigneeLst: [],
        couleurPoignee: 'blanc',
        couleurExtLst: [],
        couleurExt: null,
        hauteur: null,
        hauteur_min: null,
        hauteur_max: null,
        largeur: null,
        largeur_min: null,
        largeur_max: null,
        montantStr: null,
        montant: 0,
        message: null,
        messages: [],
        calendrier: [],
        creneau: null,
        contact: {
            nom: null,
            prenom: null,
            code_postal: null,
            email: null,
            tel: null,
            message: null,
            contact: false,
        },
        contactValid: false,
        gCalUrl: null,
        iCalUrl: null,

        getProjet: function() {
            return {
                type: this.type,
                bois: this.bois,
                hauteur: this.hauteur,
                largeur: this.largeur,
                poignee: this.poignee,
                couleurPoignee: this.couleurPoignee,
                couleurExt: this.couleurExt,
            }
        },

        texte: function(slug) {
            return this.messages[slug] ?? slug
        },

        // async function fetchDefault() {
        fetchDefault: async function() {
            //console.log('fetchDefault')
            fetch(url+'/defauts', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: this.type,
                })
            })
                .then(response => response.json())
                .then(data => {
                    this.hauteur = parseInt(data.hauteur ?? 0)
                    this.hauteur_min = parseInt(data.hauteur_min ?? 0)
                    this.hauteur_max = parseInt(data.hauteur_max ?? 0)
                    this.largeur = parseInt(data.largeur ?? 0)
                    this.largeur_min = parseInt(data.largeur_min ?? 0)
                    this.largeur_max = parseInt(data.largeur_max ?? 0)

                    this.boisLst = data.bois ?? []
                    this.bois = this.boisLst[0] ?? null

                    this.poigneeLst = data.poigneesLst ?? []
                    this.poignee = this.poigneeLst[0] ?? null

                    this.couleurPoigneeLst = data.couleurPoigneeLst ?? []
                    this.couleurPoignee = this.couleurPoigneeLst[this.poignee][0] ?? null

                    this.couleurExtLst = data.couleurExtLst ?? []
                    this.couleurExt = this.couleurExtLst[0] ?? null

                    this.message = data.error ?? null
                })
                .then(() => this.fetchMontant())
        },

        //async function fetchMontant() {
        fetchMontant: async function() {
            //console.log('fetchMontant')

            // Check couleur poignée
            const p = this.couleurPoigneeLst[this.poignee][this.couleurPoignee]??null;
            if (p === null || p < 0) {
                this.couleurPoignee = Object.keys(this.couleurPoigneeLst[this.poignee])[0];
            }

            fetch(url+'/devis', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.getProjet())
            })
                .then(response => response.json())
                .then(data => {
                    //this.montant = parseInt(data.montant ?? 0)
                    this.montant = parseInt(data.montant ?? 0)
                    this.montantStr = parseInt(data.montant ?? 0)
                        .toLocaleString('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 })
                    this.message = data.error ?? null
                })
        },

        /** @deprecated */
        fetchCalendrier: async function() {
            this.loading = true
            fetch(url+'/calendrier', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json'
                },
            })
                .then(response => response.json())
                .then(data => {
                    this.calendrier = data.calendrier ?? []
                    this.loading = false
                })
        },

        /** @deprecated */
        reserverCreneau: async function() {
            this.loading = true
            fetch(url+'/rdv', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    projet: this.getProjet(),
                    creneau: this.creneau,
                    contact: this.contact,
                })
            })
                .then(response => response.json())
                .then(data => {
                    this.message = data.error ?? null
                    if (this.message == null) this.step = 6
                    this.gCalUrl = data.gCalUrl ?? null
                    this.iCalUrl = data.iCalUrl ?? null
                    this.loading = false
                })
                .catch(err => {
                    this.message = data.error ?? err
                    this.loading = false
                })
        },

        validerProjet: async function() {
            this.loading = true
            fetch(url+'/projet', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    projet: this.getProjet(),
                    contact: this.contact,
                })
            })
                .then(response => response.json())
                .then(data => {
                    this.message = data.error ?? null
                    if (this.message == null) this.step++
                    this.loading = false
                })
                .catch(err => {
                    this.message = data.error ?? err
                    this.loading = false
                })
        },

    }
}
