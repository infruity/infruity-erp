const {test} = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
function fixture(status, data={data:[]}) {
    const context={window:{},URLSearchParams,fetch:async()=>({ok:status===200,status,redirected:false,json:async()=>data}),console};
    vm.runInNewContext(fs.readFileSync('public/assets/js/report-mobile.js','utf8'),context);
    const app=context.window.erpReportMobile({today:'2026-10-25',type:'sales',endpoint:'/sales',history:'/history',dateHistory:'/dates',branches:[]});
    app.$nextTick=()=>{};return app;
}
test('expired session and forbidden are distinct from connection failure',async()=>{
    const expired=fixture(401);await expired.fetchPage(false);assert.match(expired.error,/Sesi telah berakhir/);assert.equal(expired.loading,false);
    const denied=fixture(403);await denied.fetchPage(false);assert.match(denied.error,/tidak memiliki akses/);
});
test('failed history has error feedback, successful empty history remains empty',async()=>{
    const broken=fixture(500);await broken.openRow({product_id:1,name:'Fixture'});assert.match(broken.historyError,/Riwayat belum/);assert.equal(broken.historyLoading,false);assert.equal(broken.historyRows.length,0);
    const empty=fixture(200);await empty.openRow({product_id:1,name:'Fixture'});assert.equal(empty.historyError,'');assert.equal(empty.historyRows.length,0);
});
