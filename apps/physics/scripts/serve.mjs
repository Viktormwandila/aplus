import http from 'node:http';
import fs from 'node:fs/promises';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const root=fileURLToPath(new URL('../public/',import.meta.url));
const i=process.argv.indexOf('--port');const port=i>=0?Number(process.argv[i+1]):4173;
const types={'.html':'text/html; charset=utf-8','.js':'text/javascript; charset=utf-8','.css':'text/css; charset=utf-8','.svg':'image/svg+xml','.webmanifest':'application/manifest+json'};
http.createServer(async(req,res)=>{try{const url=new URL(req.url,'http://localhost');let name=decodeURIComponent(url.pathname);let file=path.resolve(root,'.'+name);if(file!==root.slice(0,-1)&&!file.startsWith(root)){res.writeHead(403).end();return}if(name==='/')file=path.join(root,'index.html');else if(!path.extname(file))file+='.html';let data;try{data=await fs.readFile(file)}catch{file=path.join(root,'404.html');data=await fs.readFile(file);res.statusCode=404}res.setHeader('Content-Type',types[path.extname(file)]||'application/octet-stream');res.end(data)}catch{res.writeHead(400).end('Invalid request')}}).listen(port,'0.0.0.0',()=>console.log('Local: http://localhost:'+port));
