const fs = require('fs');

const fixPagination = (filePath, storeName) => {
    let content = fs.readFileSync(filePath, 'utf-8');
    const regex = new RegExp(`<div class="d-flex justify-content-between align-items-center mt-3">\\s*<span class="text-secondary small">Showing {{ ${storeName}\\.[a-zA-Z]+\\.length }} entries of {{ ${storeName}\\.total }}(?: entries)?</span>\\s*<div class="btn-group">\\s*<button class="btn btn-sm btn-outline-secondary" :disabled="${storeName}\\.page === 1" @click="changePage\\(${storeName}\\.page - 1\\)">Previous</button>\\s*<button class="btn btn-sm btn-outline-secondary" :disabled="${storeName}\\.page \\* 10 >= ${storeName}\\.total" @click="changePage\\(${storeName}\\.page \\+ 1\\)">Next</button>\\s*</div>\\s*</div>`);
    
    const replacement = `<div class="card-footer bg-white border-top py-3 d-flex flex-column flex-md-row justify-content-between align-items-center mt-3">
        <div class="text-secondary small mb-2 mb-md-0">
          Showing {{ ${storeName}.page === 1 && ${storeName}.total === 0 ? 0 : ((${storeName}.page - 1) * 10) + 1 }} to {{ Math.min(${storeName}.page * 10, ${storeName}.total) }} of {{ ${storeName}.total }} entries
        </div>
        <div class="btn-group">
          <button class="btn btn-sm btn-outline-secondary" :disabled="${storeName}.page === 1" @click="changePage(${storeName}.page - 1)">Previous</button>
          <button class="btn btn-sm btn-outline-secondary" :disabled="${storeName}.page * 10 >= ${storeName}.total" @click="changePage(${storeName}.page + 1)">Next</button>
        </div>
      </div>`;

    if (content.match(regex)) {
        content = content.replace(regex, replacement);
        fs.writeFileSync(filePath, content, 'utf-8');
        console.log('Fixed', filePath);
    } else {
        console.log('Not matched in', filePath);
    }
};

fixPagination('resources/js/modules/grounds/views/pricing/List.vue', 'pricingStore');
fixPagination('resources/js/modules/grounds/views/List.vue', 'groundStore');
fixPagination('resources/js/modules/suppliers/views/List.vue', 'supplierStore');
fixPagination('resources/js/modules/expenses/views/List.vue', 'expenseStore');
fixPagination('resources/js/modules/pos/views/Products.vue', 'productStore');
fixPagination('resources/js/modules/purchases/views/List.vue', 'purchaseStore');
