# Python script OR-Tools à compléter
mport mysql.connector
from ortools.sat.python import cp_model

# Connexion à ta base MySQL (Laragon)
conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",  # ou ton mot de passe MySQL
    database="emploi_du_temps"
)
cursor = conn.cursor(dictionary=True)

# Charger les données nécessaires
cursor.execute("SELECT * FROM enseignants")
enseignants = cursor.fetchall()

cursor.execute("SELECT * FROM matieres")
matieres = cursor.fetchall()

cursor.execute("SELECT * FROM niveaux")
niveaux = cursor.fetchall()

cursor.execute("SELECT * FROM disponibilites")
dispos = cursor.fetchall()

# Initialisation du modèle OR-Tools
model = cp_model.CpModel()

# Paramètres
jours = ["Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi"]
heures = list(range(8, 17))  # 8h à 16h
slots = [(j, h) for j in range(len(jours)) for h in heures]

# Variables
emploi = {}
for e in enseignants:
    for m in matieres:
        if m['enseignant_id'] == e['id']:
            for n in niveaux:
                for j, h in slots:
                    emploi[(e['id'], m['id'], n['id'], j, h)] = model.NewBoolVar(
                        f'e{e["id"]}_m{m["id"]}_n{n["id"]}_j{j}_h{h}'
                    )

# Contraintes de base
for e in enseignants:
    dispo_e = [(d['jour'], d['heure']) for d in dispos if d['enseignant_id'] == e['id']]
    for key in emploi:
        eid, mid, nid, j, h = key
        if eid == e['id'] and (jours[j], h) not in dispo_e:
            model.Add(emploi[key] == 0)

# Contrainte 1: Un enseignant ne peut avoir qu'un seul cours à la fois
for e in enseignants:
    for j, h in slots:
        model.Add(sum(
            emploi.get((e['id'], m['id'], n['id'], j, h), 0)
            for m in matieres for n in niveaux
        ) <= 1)

# Solve
solver = cp_model.CpSolver()
status = solver.Solve(model)

# Nettoyer ancienne génération
cursor.execute("DELETE FROM emplois_du_temps")
conn.commit()

if status == cp_model.FEASIBLE or status == cp_model.OPTIMAL:
    for key, var in emploi.items():
        if solver.Value(var):
            eid, mid, nid, j, h = key
            cursor.execute("""
                INSERT INTO emplois_du_temps (enseignant_id, matiere_id, niveau_id, jour, heure)
                VALUES (%s, %s, %s, %s, %s)
            """, (eid, mid, nid, jours[j], h))

    conn.commit()
    print("✅ Emploi du temps généré avec succès.")
else:
    print("❌ Aucune solution trouvée. Vérifiez les contraintes.")

cursor.close()
conn.close()