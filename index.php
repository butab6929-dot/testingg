<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Block absurd</title>

<style>
:root{
    --bg:#0f1117;
    --panel:#181c25;
    --cell:#222836;
    --border:#2f3546;
    --text:#ffffff;
    --accent:#00e5ff;
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    -webkit-tap-highlight-color: transparent;
}

body{
    background:linear-gradient(180deg,#0b0d13,#171b24);
    color:white;
    font-family:Segoe UI, sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    padding:20px;
}

.container{
    width:100%;
    max-width:600px;
}

header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    gap:10px;
}

.title{
    font-size:2rem;
    font-weight:800;
    color:#00e5ff;
    text-shadow:0 0 15px #00e5ff;
}

.score-box{
    background:var(--panel);
    padding:10px 15px;
    border-radius:14px;
    text-align:center;
    min-width:100px;
}

.score-label{
    font-size:.8rem;
    opacity:.7;
}

.score-value{
    font-size:1.3rem;
    font-weight:bold;
}

.board{
    width:100%;
    aspect-ratio:1;
    display:grid;
    grid-template-columns:repeat(8,1fr);
    grid-template-rows:repeat(8,1fr);
    gap:4px;
    background:var(--panel);
    padding:8px;
    border-radius:20px;
}

.cell{
    background:var(--cell);
    border-radius:8px;
    transition:.25s;
    position:relative;
}

.filled{
    animation:pop .18s ease;
}

@keyframes pop{
    from{
        transform:scale(.6);
    }
    to{
        transform:scale(1);
    }
}

.blast{
    animation:blast .45s forwards;
}

@keyframes blast{
    0%{
        transform:scale(1);
        opacity:1;
    }
    100%{
        transform:scale(0);
        opacity:0;
    }
}

.pieces{
    margin-top:25px;
    display:flex;
    justify-content:space-between;
    gap:10px;
    min-height:110px;
}

.piece{
    flex:1;
    background:var(--panel);
    border-radius:16px;
    display:flex;
    justify-content:center;
    align-items:center;
    cursor:grab;
    touch-action:none;
    min-height:100px;
    position:relative;
}

.piece-grid{
    display:grid;
    gap:4px;
}

.block{
    width:22px;
    height:22px;
    background-size:cover;
    image-rendering:pixelated;
}

.modal{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.75);
    display:flex;
    justify-content:center;
    align-items:center;
    visibility:hidden;
    opacity:0;
    transition:.3s;
}

.modal.show{
    visibility:visible;
    opacity:1;
}

.modal-content{
    background:#1b2130;
    padding:30px;
    border-radius:20px;
    text-align:center;
    width:90%;
    max-width:350px;
}

.modal-content h2{
    margin-bottom:15px;
    color:#ff4d6d;
}

.modal-content button{
    margin-top:20px;
    padding:12px 20px;
    border:none;
    border-radius:12px;
    background:#00e5ff;
    color:black;
    font-weight:bold;
    cursor:pointer;
}

@media(max-width:600px){
    .block{
        width:18px;
        height:18px;
    }

    .title{
        font-size:1.5rem;
    }

    header{
        flex-wrap:wrap;
    }
}

.dragging{
    position:fixed;
    z-index:9999;
    pointer-events:none;
    display:grid;
    opacity:.85;
    transform:translate(-50%,-50%) scale(1.2);
}

.invalid-preview{
    background:#ff4d4d !important;
    opacity:.5;
}

@keyframes blast{
    0%{
        transform:scale(1);
        opacity:1;
    }

    50%{
        transform:scale(1.4);
        filter:brightness(2);
    }

    100%{
        transform:scale(0);
        opacity:0;
    }
}

.preview-valid{
    outline:3px solid #00ff88;
    outline-offset:-2px;
    box-shadow:0 0 10px #00ff88;
}

.preview-invalid{
    outline:3px solid #ff4d4d;
    outline-offset:-2px;
    box-shadow:0 0 10px #ff4d4d;
}


</style>
</head>
<body>

<div class="container">

<header>
<div class="title">CRAFT BLOCK</div>

<div class="score-box">
<div class="score-label">SCORE</div>
<div class="score-value" id="score">0</div>
</div>

<div class="score-box">
<div class="score-label">HIGH</div>
<div class="score-value" id="highscore">0</div>
</div>
</header>

<div class="board" id="board"></div>

<div class="pieces" id="pieces"></div>

</div>

<div class="modal" id="gameOverModal">
<div class="modal-content">
<h2>Yahaha kalah</h2>
<p>Skor Akhir:</p>
<h1 id="finalScore">0</h1>
<button onclick="restartGame()">Main Pou aj</button>
</div>
</div>

<script>
const placeSound = new Audio("sounds/place_new.mp3");
const clearSound = new Audio("sounds/clear_new.mp3");
const gameOverSound = new Audio("sounds/gameover.mp3");
const SIZE = 8;
const BLOCK_TYPES = [
    "stone.jpg",
    "grass_block_top.jpg",
    "oak_planks.jpg",
    "dirt.jpg",
    "cobblestone.png"
];

const SHAPES = [
[[1]],

[[1,1]],

[[1],[1]],

[[1,1,1]],

[[1],[1],[1]],

[[1,1],[1,1]],

[[1,0],[1,1]],

[[0,1],[1,1]],

[[1,1,1],[0,1,0]],

[[1,1,1],[1,0,0]],

[[1,1,1],[0,0,1]]
];

let board = [];
let pieces = [];
let score = 0;
let highScore = localStorage.getItem("blockBlastHigh") || 0;

const boardEl = document.getElementById("board");
const piecesEl = document.getElementById("pieces");

document.getElementById("highscore").textContent = highScore;
function minecraftTexture(color){
    return `
    linear-gradient(
        45deg,
        rgba(255,255,255,.12) 25%,
        transparent 25%,
        transparent 50%,
        rgba(0,0,0,.08) 50%,
        rgba(0,0,0,.08) 75%,
        transparent 75%
    ),
    ${color}
    `;
}

function initBoard(){
    board = Array.from({length:SIZE},()=>Array(SIZE).fill(null));

    boardEl.innerHTML = "";

    for(let y=0;y<SIZE;y++){
        for(let x=0;x<SIZE;x++){
            const cell=document.createElement("div");
            cell.className="cell";
            cell.dataset.x=x;
            cell.dataset.y=y;
            boardEl.appendChild(cell);
        }
    }
}

function renderBoard(){
    [...boardEl.children].forEach(cell=>{
        let x=+cell.dataset.x;
        let y=+cell.dataset.y;

        cell.className="cell";

        if(board[y][x]){
            cell.classList.add("filled");
            cell.style.backgroundImage =
`url(textures/${board[y][x]})`;

cell.style.backgroundSize = "cover";

cell.style.backgroundSize = "cover";
        }else{
            cell.style.background="";
        }
    });
}

function randomShape(){
    return JSON.parse(JSON.stringify(
        SHAPES[Math.floor(Math.random()*SHAPES.length)]
    ));
}

function randomBlock(){
    return BLOCK_TYPES[
        Math.floor(Math.random()*BLOCK_TYPES.length)
    ];
}

function generatePieces(){
    pieces=[];

    for(let i=0;i<3;i++){
        pieces.push({
    shape:randomShape(),
    texture:randomBlock()
});
    }

    renderPieces();
}

function renderPieces(){
    piecesEl.innerHTML="";

    pieces.forEach((piece,index)=>{

        const wrapper=document.createElement("div");
        wrapper.className="piece";
        wrapper.dataset.index=index;

        const rows=piece.shape.length;
        const cols=Math.max(...piece.shape.map(r=>r.length));

        const grid=document.createElement("div");
        grid.className="piece-grid";
        grid.style.gridTemplateColumns=`repeat(${cols},20px)`;

        for(let r=0;r<rows;r++){
            for(let c=0;c<cols;c++){

                const b=document.createElement("div");

                if(piece.shape[r][c]){
                    b.className="block";
                    b.style.backgroundImage =
                    `url(textures/${piece.texture})`;
                    b.style.backgroundSize = "cover";
                }else{
                    b.style.visibility="hidden";
                }

                grid.appendChild(b);
            }
        }

        wrapper.appendChild(grid);

        enableDrag(wrapper,piece,index);

        piecesEl.appendChild(wrapper);
    });
}

function canPlace(shape,row,col){

    for(let y=0;y<shape.length;y++){
        for(let x=0;x<shape[y].length;x++){

            if(!shape[y][x]) continue;

            let by=row+y;
            let bx=col+x;

            if(
                by<0 || bx<0 ||
                by>=SIZE || bx>=SIZE
            ) return false;

            if(board[by][bx]) return false;
        }
    }

    return true;
}

function placeShape(shape,row,col,texture){

    let blocksPlaced = 0;

    for(let y=0;y<shape.length;y++){
        for(let x=0;x<shape[y].length;x++){

            if(shape[y][x]){

                board[row+y][col+x] = texture;
                blocksPlaced++;

            }
        }
    }

    placeSound.currentTime = 0;
    placeSound.play();

    addScore(blocksPlaced * 10);

    renderBoard();

    setTimeout(checkLines,120);
}

function checkLines(){

    let rows=[];
    let cols=[];

    for(let y=0;y<SIZE;y++){
        if(board[y].every(c=>c))
            rows.push(y);
    }

    for(let x=0;x<SIZE;x++){

        let full=true;

        for(let y=0;y<SIZE;y++){
            if(!board[y][x]){
                full=false;
                break;
            }
        }

        if(full) cols.push(x);
    }

    if(rows.length===0 && cols.length===0){
        checkGameOver();
        return;
    }

    const combo=rows.length+cols.length;

clearSound.currentTime = 0;
clearSound.play();

    rows.forEach(r=>{
        for(let x=0;x<SIZE;x++){
            animateBlast(r,x);
        }
    });

    cols.forEach(c=>{
        for(let y=0;y<SIZE;y++){
            animateBlast(y,c);
        }
    });

    setTimeout(()=>{

        rows.forEach(r=>{
            for(let x=0;x<SIZE;x++)
                board[r][x]=null;
        });

        cols.forEach(c=>{
            for(let y=0;y<SIZE;y++)
                board[y][c]=null;
        });

        addScore(combo*100*combo);

        renderBoard();

        checkGameOver();

    },450);
}

function animateBlast(y,x){

    const cell=[...boardEl.children]
        .find(c=>
            +c.dataset.x===x &&
            +c.dataset.y===y
        );

    if(cell)
        cell.classList.add("blast");
}

function addScore(v){

    score+=v;

    if(score>highScore){
        highScore=score;
        localStorage.setItem(
            "blockBlastHigh",
            highScore
        );
    }

    document.getElementById("score").textContent=score;
    document.getElementById("highscore").textContent=highScore;
}

function removePiece(index){

    pieces.splice(index,1);

    if(pieces.length===0){
        generatePieces();
    }else{
        renderPieces();
    }
}

function boardCellFromPoint(x,y){

    const rect=boardEl.getBoundingClientRect();

    if(
        x<rect.left || x>rect.right ||
        y<rect.top || y>rect.bottom
    ) return null;

    const cellW=rect.width/SIZE;
    const cellH=rect.height/SIZE;

    return {
        col:Math.floor((x-rect.left)/cellW),
        row:Math.floor((y-rect.top)/cellH)
    };
}
function clearPreview(){

    document
        .querySelectorAll(
            ".preview-valid,.preview-invalid"
        )
        .forEach(cell=>{

            cell.classList.remove(
                "preview-valid",
                "preview-invalid"
            );

        });
}

function showPreview(shape,row,col){

    clearPreview();

    const valid = canPlace(
        shape,
        row,
        col
    );

    for(let y=0;y<shape.length;y++){

        for(let x=0;x<shape[y].length;x++){

            if(!shape[y][x]) continue;

            const by = row + y;
            const bx = col + x;

            if(
                by < 0 ||
                bx < 0 ||
                by >= SIZE ||
                bx >= SIZE
            ) continue;

            const cell = [...boardEl.children]
                .find(c =>
                    +c.dataset.x === bx &&
                    +c.dataset.y === by
                );

            if(cell){

                cell.classList.add(
                    valid
                    ? "preview-valid"
                    : "preview-invalid"
                );

            }
        }
    }
}

function enableDrag(element,piece,index){

    let clone=null;

    function start(clientX,clientY){

    clone = element
        .querySelector(".piece-grid")
        .cloneNode(true);

    clone.classList.add("dragging");

    document.body.appendChild(clone);

    move(clientX,clientY);
}

     function move(clientX,clientY){

    if(!clone) return;

    clone.style.left = clientX + "px";
    // Kita simpan posisi Y block yang udah diberi offset ke atas
    const blockTop = clientY - 80; 
    clone.style.top = blockTop + "px";

    // BENAR: Deteksi grid berdasarkan posisi block (blockTop), bukan jempol (clientY)
    const pos = boardCellFromPoint(
        clientX,
        blockTop
    );


    if(pos){

        showPreview(
            piece.shape,
            pos.row,
            pos.col
        );

    }else{

        clearPreview();

    }
}

        function end(clientX,clientY){

    clearPreview();

    if(!clone) return;

    // BENAR: Pas dilepas, hitung juga berdasarkan koordinat block (dikurangi 80)
    const blockTop = clientY - 80;

    let pos = boardCellFromPoint(
        clientX,
        blockTop
    );


    clone.remove();
    clone = null;

    if(
        pos &&
        canPlace(
            piece.shape,
            pos.row,
            pos.col
        )
    ){

        placeShape(
            piece.shape,
            pos.row,
            pos.col,
            piece.texture
        );

        removePiece(index);
    }
}

    element.addEventListener("mousedown",e=>{

        e.preventDefault();

        start(e.clientX,e.clientY);

        const mm=e2=>move(
            e2.clientX,
            e2.clientY
        );

        const mu=e2=>{
            end(
                e2.clientX,
                e2.clientY
            );

            document.removeEventListener("mousemove",mm);
            document.removeEventListener("mouseup",mu);
        };

        document.addEventListener("mousemove",mm);
        document.addEventListener("mouseup",mu);
    });

    element.addEventListener("touchstart",e=>{

        let t=e.touches[0];

        start(t.clientX,t.clientY);

    });

    element.addEventListener("touchmove",e=>{

        let t=e.touches[0];

        move(t.clientX,t.clientY);

    });

    element.addEventListener("touchend",e=>{

        let t=e.changedTouches[0];

        end(t.clientX,t.clientY);

    });
}

/* -------------------------
   GAME OVER DETECTION
-------------------------- */

function hasValidMove(piece){

    for(let y=0;y<SIZE;y++){
        for(let x=0;x<SIZE;x++){

            if(
                canPlace(
                    piece.shape,
                    y,
                    x
                )
            ){
                return true;
            }
        }
    }

    return false;
}

function checkGameOver(){

    if(pieces.length===0) return;

    let possible=false;

    for(const piece of pieces){

        if(hasValidMove(piece)){
            possible=true;
            break;
        }
    }

    if(!possible){
        showGameOver();
    }
}

function showGameOver(){

    document.getElementById(
        "finalScore"
    ).textContent=score;

    document.getElementById(
        "gameOverModal"
    ).classList.add("show");
}

function restartGame(){

    score=0;

    document.getElementById(
        "score"
    ).textContent=0;

    document.getElementById(
        "gameOverModal"
    ).classList.remove("show");

    initBoard();
    generatePieces();
}

/* -------------------------
   START GAME
-------------------------- */

initBoard();
generatePieces();
renderBoard();

</script>

</body>
</html>