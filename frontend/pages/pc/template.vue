<template>
  <div class="autodoc-page">
    <!-- 模式提示横幅 -->
    <div class="mode-banner">
      <div class="mode-banner-head">
        <span class="mode-banner-icon" :class="isEditMode ? 'icon-edit' : 'icon-create'">
          <svg v-if="isEditMode" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/>
            <line x1="9" y1="15" x2="15" y2="15"/>
          </svg>
        </span>
        <h3 class="mode-banner-title">{{ isEditMode ? '编辑模板' : '新建模板' }}</h3>
        <span class="mode-tag" :class="isEditMode ? 'tag-edit' : 'tag-create'">
          <span class="mode-tag-dot"></span>
          {{ isEditMode ? '编辑模式' : '创建模式' }}
        </span>
      </div>
      <p class="mode-banner-desc">
        <template v-if="isEditMode">正在编辑模板<span v-if="originalTemplateName" class="mode-banner-name">「{{ originalTemplateName }}」</span>，保存后将更新原模板的封面与参数</template>
        <template v-else>配置完成后保存为新模板，可在「我的模板」列表中管理</template>
      </p>
    </div>

    <div class="autodoc-layout">
      <aside class="tab-nav">
        <div class="tab-nav-title">配置项</div>
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="['tab-btn', { active: activeTab === tab.key }]"
          @click="activeTab = tab.key"
        >
          <svg class="tab-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" v-html="tab.svg"></svg>
          <span class="tab-label">{{ tab.label }}</span>
        </button>
      </aside>

      <div class="tab-content">
        <!-- ========== 封面设置 ========== -->
        <section v-show="activeTab === 'cover'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">封面设置</h3>
              <span class="section-badge">仅需包含封面板块</span>
            </div>
            <div
              class="upload-area"
              :class="{ 'drag-over': isDragging, 'has-file': selectedFile }"
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleFileDrop"
            >
              <input type="file" ref="fileInput" style="display: none;" accept=".docx" @change="handleFileChange">
              <div v-if="!selectedFile" class="upload-content" @click="triggerFileUpload">
                <div class="upload-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                  </svg>
                </div>
                <div class="upload-text">
                  <div class="upload-primary-text">点击或拖拽文件上传</div>
                  <div class="upload-secondary-text">仅支持 docx 格式 · 最大 10MB</div>
                </div>
              </div>
              <div v-else class="file-selected">
                <div class="selected-header">
                  <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                  <span>{{ coverOssUrl ? '封面上传成功' : '已选择论文文档' }}</span>
                </div>
                <div class="file-name-tag">{{ selectedFile.name }}</div>
                <div class="file-size-text">{{ formatFileSize(selectedFile.size) }}</div>
                <div v-if="uploadingCover" class="file-status-text uploading">上传中...</div>
                <div v-else-if="coverOssUrl" class="file-status-text success">已上传至云端</div>
                <div class="file-actions">
                  <button class="btn-replace" @click="triggerFileUpload">更换文件</button>
                  <button class="btn-delete" @click="removeFile">删除文件</button>
                </div>
              </div>
            </div>

            <div class="upload-requirements">
              <div class="requirement-title">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                文档要求
              </div>
              <ul class="requirement-list">
                <li><strong>格式要求：</strong>仅支持 .docx 格式（Word 2007+），不支持 .doc 格式直接改后缀</li>
                <li><strong>文件大小：</strong>最大 10MB</li>
                <li><strong>内容要求：</strong>文档应仅包含封面板块，内容干净简洁</li>
                <li><strong>禁止内容：</strong>请勿包含批注、修订标记、格式说明、隐藏文字等</li>
                <li><strong>格式建议：</strong>建议使用标准页面设置，避免复杂排版和特殊字体</li>
                <li>
                  <strong>封面模板：</strong>
                  <a class="demo-link" href="https://adhelp-muban.oss-cn-beijing.aliyuncs.com/actions/mubanfengmain.docx" target="_blank" rel="noopener">
                    下载参考封面
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="7 10 12 15 17 10"></polyline>
                      <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                  </a>
                </li>
              </ul>
            </div>

            <div class="action-bar">
              <button class="ds-btn ds-btn-primary" @click="saveSettings">{{ isEditMode ? '更新模板' : '保存模板' }}</button>
              <button class="ds-btn ds-btn-secondary" @click="demonstrate" :disabled="demonstrating">
                <span v-if="demonstrating" class="btn-loading"></span>
                {{ demonstrating ? '生成中...' : '效果预览' }}
              </button>
            </div>
          </div>
        </section>

        <!-- ========== 摘要和关键词设置 ========== -->
        <section v-show="activeTab === 'abstract'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">摘要和关键词设置</h3>
            </div>

            <div class="setting-block">
              <div class="block-title">基础配置</div>
              <div class="form-row cols-2">
                <TemplateToggleItem label="启用英文摘要和关键词" v-model="form.enableEnglish" :defaultVal="true" />
                <TemplateToggleItem label="启用特殊设置" v-model="form.specialSetting" />
              </div>
            </div>

            <div v-if="form.specialSetting" class="setting-block">
              <div class="block-title">特殊设置</div>
              <div class="form-row">
                <label>摘要标题上面加论文标题</label>
                <input type="text" v-model="form.abstractTitleTopTitle" class="ds-input" placeholder="请输入指定内容，没有请留空">
              </div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.specialTitleFont" :options="chineseFonts" />
                <TemplateFormSelect label="字号" v-model="form.specialTitleSize" :options="fontSizes" />
                <TemplateSpacingGroup label="行距" v-model:rule="form.specialTitleSpacingRule" v-model:value="form.specialTitleSpacingValue" v-model:unit="form.specialTitleSpacingRuleUnit" />
                <TemplateInputWithUnit label="段前间距" v-model:value="form.specialTitleBefore" v-model:unit="form.specialTitleBeforeUnit" :units="spacingUnits" />
                <TemplateInputWithUnit label="段后间距" v-model:value="form.specialTitleAfter" v-model:unit="form.specialTitleAfterUnit" :units="spacingUnits" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">摘要关键词</div>
              <div class="form-flex">
                <TemplateFormInput label="摘要标题与正文间空行数" v-model.number="form.abstractTitleLine" unit="行" />
                <TemplateFormInput label="关键词与摘要正文之间空行数" v-model.number="form.abstractContentLine" unit="行" />
                <TemplateFormInput label="关键词数量" v-model.number="form.abstractKeywordsNum" unit="个" />
                <TemplateFormInput label="关键词分割符号" v-model="form.abstractKeywordsSymbol" unit="一般以 , 和 ; 分割" :unitStyle="{ color: '#ef4444', fontSize: '12px' }" />
                <TemplateFormInput label="关键词标题与关键词之间空" v-model.number="form.abstractKeywordsIntervalNum" unit="个英文字符" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">中文摘要《标题》格式</div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.cnAbstractTitleFont" :options="chineseFonts" />
                <TemplateFormSelect label="字号" v-model="form.cnAbstractTitleSize" :options="fontSizes" defaultVal="小四" />
                <TemplateFormSelect label="对齐" v-model="form.cnAbstractTitleAlign" :options="alignOptions" defaultVal="center" />
                <TemplateFormInput label="两字之间空" v-model.number="form.cnAbstractTitleInterval" unit="个英文字符" />
                <TemplateToggleItem label="加粗" v-model="form.cnAbstractTitleBold" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">中文摘要《正文》格式</div>
              <div class="form-flex">
                <TemplateInputWithUnit label="首行缩进" v-model:value="form.cnAbstractFirstLineIndent" v-model:unit="form.cnAbstractIndentUnit" :units="indentUnits" />
                <TemplateFormSelect label="字体" v-model="form.cnAbstractFont" :options="chineseFonts" />
                <TemplateFormSelect label="字号" v-model="form.cnAbstractSize" :options="fontSizes" defaultVal="小四" />
                <TemplateSpacingGroup label="行距" v-model:rule="form.cnAbstractLineSpacingRule" v-model:value="form.cnAbstractLineSpacingValue" v-model:unit="form.cnAbstractLineSpacingRuleUnit" />
                <TemplateFormSelect label="对齐" v-model="form.cnAbstractAlign" :options="alignOptions" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">中文关键词格式</div>
              <div class="form-flex">
                <TemplateFormSelect label="标题字体" v-model="form.cnAbstractKeywordsFont" :options="chineseFonts" defaultVal="黑体" />
                <TemplateFormSelect label="标题字号" v-model="form.cnAbstractKeywordsSize" :options="fontSizes" defaultVal="小四" />
                <TemplateToggleItem label="标题加粗" v-model="form.cnAbstractKeywordsBold" :defaultVal="true" />
                <TemplateFormSelect label="内容字体" v-model="form.cnAbstractKeywordsContentFont" :options="chineseFonts" />
                <TemplateFormSelect label="内容字号" v-model="form.cnAbstractKeywordsContentSize" :options="fontSizes" defaultVal="小四" />
              </div>
            </div>

            <div v-if="form.enableEnglish" class="setting-block-group">
              <div class="block-group-label">英文格式</div>

              <div class="setting-block">
                <div class="block-title">英文摘要《标题》格式</div>
                <div class="form-flex">
                  <TemplateFormSelect label="字体" v-model="form.enAbstractTitleFont" :options="englishFonts" defaultVal="Times New Roman" />
                  <TemplateFormSelect label="字号" v-model="form.enAbstractTitleSize" :options="fontSizes" defaultVal="小四" />
                  <TemplateFormSelect label="对齐" v-model="form.enAbstractTitleAlign" :options="alignOptions" defaultVal="center" />
                  <TemplateFormInput label="两字之间空" v-model.number="form.enAbstractTitleInterval" unit="个英文字符" />
                  <TemplateToggleItem label="加粗" v-model="form.enAbstractTitleBold" />
                </div>
              </div>

              <div class="setting-block">
                <div class="block-title">英文摘要《正文》格式</div>
                <div class="form-flex">
                  <TemplateInputWithUnit label="首行缩进" v-model:value="form.enAbstractFirstLineIndent" v-model:unit="form.enAbstractIndentUnit" :units="indentUnits" />
                  <TemplateFormSelect label="字体" v-model="form.enAbstractFont" :options="englishFonts" defaultVal="Times New Roman" />
                  <TemplateFormSelect label="字号" v-model="form.enAbstractSize" :options="fontSizes" defaultVal="小四" />
                  <TemplateSpacingGroup label="行距" v-model:rule="form.enAbstractLineSpacingRule" v-model:value="form.enAbstractLineSpacingValue" v-model:unit="form.enAbstractLineSpacingRuleUnit" />
                  <TemplateFormSelect label="对齐" v-model="form.enAbstractAlign" :options="alignOptions" />
                </div>
              </div>

              <div class="setting-block">
                <div class="block-title">英文关键词格式</div>
                <div class="form-flex">
                  <TemplateFormSelect label="标题字体" v-model="form.enAbstractKeywordsFont" :options="englishFonts" defaultVal="Times New Roman" />
                  <TemplateFormSelect label="标题字号" v-model="form.enAbstractKeywordsSize" :options="fontSizes" defaultVal="小四" />
                  <TemplateToggleItem label="标题加粗" v-model="form.enAbstractKeywordsBold" :defaultVal="true" />
                  <TemplateFormSelect label="内容字体" v-model="form.enAbstractKeywordsContentFont" :options="englishFonts" defaultVal="Times New Roman" />
                  <TemplateFormSelect label="内容字号" v-model="form.enAbstractKeywordsContentSize" :options="fontSizes" defaultVal="小四" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ========== 目录设置 ========== -->
        <section v-show="activeTab === 'catalog'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">目录设置</h3>
            </div>

            <div class="setting-block">
              <div class="toggle-row">
                <TemplateToggleItem label="启用三级目录" v-model="form.switchThird" :defaultVal="true" hint="不启用则为二级目录" />
                <TemplateToggleItem label="显示目录标题" v-model="form.showTocTitle" :defaultVal="true" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">目录标题格式</div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.tocTitleFont" :options="cnEnFonts" defaultVal="黑体" />
                <TemplateFormSelect label="字号" v-model="form.tocTitleSize" :options="fontSizes" defaultVal="小三" />
                <TemplateFormSelect label="对齐方式" v-model="form.tocTitleAlign" :options="alignOptionsBasic" defaultVal="center" />
                <TemplateSpacingGroup label="标题行距" v-model:rule="form.tocTitleLineSpacingRule" v-model:value="form.tocTitleLineSpacingValue" :unit="'line'" size="small" />
                <TemplateFormInput label="段前距" v-model.number="form.tocTitleSpaceBefore" unit="磅" />
                <TemplateFormInput label="段后距" v-model.number="form.tocTitleSpaceAfter" unit="磅" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">目录内容格式</div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.tocFont" :options="cnEnFonts" />
                <TemplateFormSelect label="字号" v-model="form.tocSize" :options="fontSizes" defaultVal="小四" />
                <TemplateFormSelect label="制表符前导符" v-model="form.tocLeader" :options="leaderOptions" defaultVal="dot" />
                <TemplateSpacingGroup label="行距" v-model:rule="form.tocLineSpacingRule" v-model:value="form.tocLineSpacingValue" v-model:unit="form.tocLineSpacingRuleUnit" />
                <TemplateFormSelect label="对齐方式" v-model="form.tocAlign" :options="alignOptions" />
              </div>
            </div>
          </div>
        </section>

        <!-- ========== 标题设置 ========== -->
        <section v-show="activeTab === 'title'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">标题设置</h3>
            </div>
            <div v-for="(level, idx) in titleLevels" :key="level.key" class="setting-block">
              <div class="block-title">{{ level.label }}</div>
              <div class="form-flex">
                <TemplateFormSelect label="编号格式" v-model="form[level.numberFormatKey]" :options="level.numberFormats" :defaultVal="level.defaultNumberFormat" />
                <TemplateFormSelect label="分隔符" v-model="form[level.separatorKey]" :options="separatorOptions" :defaultVal="level.defaultSeparator" />
                <TemplateFormSelect label="字体" v-model="form[level.fontKey]" :options="allFonts" :defaultVal="level.defaultFont" />
                <TemplateFormSelect label="字号" v-model="form[level.sizeKey]" :options="fontSizes" :defaultVal="level.defaultSize" />
                <TemplateFormSelect label="对齐" v-model="form[level.alignKey]" :options="level.alignOpts" :defaultVal="level.defaultAlign" />
                <TemplateToggleItem label="加粗" v-model="form[level.boldKey]" :defaultVal="true" />
              </div>
              <div class="form-flex">
                <TemplateSpacingGroup label="行距" v-model:rule="form[level.lineSpacingRuleKey]" v-model:value="form[level.lineSpacingValueKey]" v-model:unit="form[level.lineSpacingUnitKey]" />
                <TemplateInputWithUnit label="段前距" v-model:value="form[level.spaceBeforeKey]" v-model:unit="form[level.spaceBeforeUnitKey]" :units="spacingUnits" />
                <TemplateInputWithUnit label="段后距" v-model:value="form[level.spaceAfterKey]" v-model:unit="form[level.spaceAfterUnitKey]" :units="spacingUnits" />
              </div>
              <div v-if="idx < titleLevels.length - 1" class="block-divider"></div>
            </div>
          </div>
        </section>

        <!-- ========== 正文设置 ========== -->
        <section v-show="activeTab === 'content'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">正文设置</h3>
            </div>

            <div class="setting-block">
              <div class="block-title">数字和字母</div>
              <div class="form-flex">
                <TemplateToggleItem label="启用数字和字母单独设置" v-model="form.contentNumEnSwitch" :defaultVal="true" />
                <TemplateFormSelect label="字体" v-model="form.contentNumEnFont" :options="allFonts" />
                <TemplateFormSelect label="字号" v-model="form.contentNumEnSize" :options="fontSizes" defaultVal="小四" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">基本格式</div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.contentFont" :options="allFonts" />
                <TemplateFormSelect label="字号" v-model="form.contentSize" :options="fontSizes" defaultVal="小四" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">段落设置</div>
              <div class="form-flex">
                <TemplateFormSelect label="对齐方式" v-model="form.paragraphAlign" :options="alignOptions" />
                <TemplateInputWithUnit label="首行缩进" v-model:value="form.firstLineIndent" v-model:unit="form.indentUnit" :units="indentUnits" />
                <TemplateSpacingGroup label="行距" v-model:rule="form.lineSpacingRule" v-model:value="form.lineSpacingValue" v-model:unit="form.lineSpacingRuleUnit" />
                <TemplateInputWithUnit label="左缩进" v-model:value="form.leftIndent" v-model:unit="form.leftIndentUnit" :units="indentUnits" />
                <TemplateInputWithUnit label="右缩进" v-model:value="form.rightIndent" v-model:unit="form.rightIndentUnit" :units="indentUnits" />
              </div>
              <div class="form-flex">
                <TemplateInputWithUnit label="段前间距" v-model:value="form.spaceBefore" v-model:unit="form.spaceBeforeUnit" :units="spacingUnits" />
                <TemplateInputWithUnit label="段后间距" v-model:value="form.spaceAfter" v-model:unit="form.spaceAfterUnit" :units="spacingUnits" />
              </div>
            </div>
          </div>
        </section>

        <!-- ========== 页眉页脚设置 ========== -->
        <section v-show="activeTab === 'headerFooter'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">页眉页脚设置</h3>
            </div>

            <div class="setting-block">
              <div class="block-title">页眉设置</div>
              <TemplateToggleItem label="启用页眉" v-model="form.enableHeader" />
              <div v-if="form.enableHeader" class="setting-block-group" style="margin-top:16px">
                <div v-if="!form.enableDifferentHeader">
                  <div class="form-row cols-2">
                    <TemplateFormInput label="页眉内容" v-model="form.headerContent" placeholder="请输入页眉内容" wide />
                    <TemplateToggleItem label="使用当前一级标题" v-model="form.useCurrentTitle" />
                  </div>
                  <div v-if="form.headerContent && form.useCurrentTitle" class="form-row">
                    <label>内容位置</label>
                    <div class="radio-group">
                      <label><input type="radio" v-model="form.headerPosition" value="contentLeft"> 固定内容在左，一级标题在右</label>
                      <label><input type="radio" v-model="form.headerPosition" value="titleLeft"> 一级标题在左，固定内容在右</label>
                    </div>
                  </div>
                  <div class="form-flex">
                    <TemplateFormSelect label="字体" v-model="form.headerFont" :options="cnEnFonts" />
                    <TemplateFormSelect label="字号" v-model="form.headerSize" :options="fontSizes" defaultVal="小四" />
                    <TemplateFormSelect label="对齐方式" v-model="form.headerAlign" :options="alignOptionsBasic" defaultVal="center" />
                    <TemplateToggleItem label="加粗" v-model="form.headerBold" />
                  </div>
                  <div class="block-group-label">底部边框设置</div>
                  <div class="form-flex">
                    <TemplateFormSelect label="边框样式" v-model="form.headerBorderStyle" :options="borderStyleOptions" defaultVal="single" />
                    <TemplateFormSelect label="边框颜色" v-model="form.headerBorderColor" :options="borderColorOptions" defaultVal="auto" />
                    <TemplateFormSelect label="边框粗细" v-model="form.headerBorderSize" :options="borderSizeOptions" defaultVal="2" />
                    <TemplateFormInput label="边框间距" v-model.number="form.headerBorderSpace" unit="磅" />
                    <TemplateToggleItem label="边框阴影" v-model="form.headerBorderShadow" />
                  </div>
                  <div class="form-flex">
                    <TemplateFormInput label="页眉上边距" v-model.number="form.headerTopMargin" unit="厘米" />
                    <TemplateFormInput label="页脚下边距" v-model.number="form.headerBottomMargin" unit="厘米" />
                  </div>
                </div>
                <div class="even-odd-toggle-bar">
                  <TemplateToggleItem label="启用奇偶页不同页眉" v-model="form.enableDifferentHeader" />
                </div>
                <div v-if="form.enableDifferentHeader" class="setting-block-group" style="margin-top:12px; background:#f0fdfa; border-left:3px solid #14b8a6">
                  <div class="block-group-label">奇数页</div>
                  <div class="form-row cols-2">
                    <TemplateFormInput label="页眉内容" v-model="form.oddHeaderContent" placeholder="请输入奇数页页眉内容" wide />
                    <TemplateToggleItem label="使用当前一级标题" v-model="form.oddUseCurrentTitle" />
                  </div>
                  <div v-if="form.oddHeaderContent && form.oddUseCurrentTitle" class="form-row">
                    <label>内容位置</label>
                    <div class="radio-group">
                      <label><input type="radio" v-model="form.oddHeaderPosition" value="contentLeft"> 固定内容在左，一级标题在右</label>
                      <label><input type="radio" v-model="form.oddHeaderPosition" value="titleLeft"> 一级标题在左，固定内容在右</label>
                    </div>
                  </div>
                  <div class="form-flex">
                    <TemplateFormSelect label="字体" v-model="form.oddHeaderFont" :options="cnEnFonts" />
                    <TemplateFormSelect label="字号" v-model="form.oddHeaderSize" :options="fontSizes" defaultVal="小四" />
                    <TemplateFormSelect label="对齐方式" v-model="form.oddHeaderAlign" :options="alignOptionsBasic" defaultVal="center" />
                    <TemplateToggleItem label="加粗" v-model="form.oddHeaderBold" />
                    <TemplateFormInput label="上边距" v-model.number="form.oddHeaderTopMargin" unit="厘米" />
                  </div>

                  <div class="block-divider" style="margin:12px 0"></div>
                  <div class="block-group-label">偶数页</div>
                  <div class="form-row cols-2">
                    <TemplateFormInput label="页眉内容" v-model="form.evenHeaderContent" placeholder="请输入偶数页页眉内容" wide />
                    <TemplateToggleItem label="使用当前一级标题" v-model="form.evenUseCurrentTitle" />
                  </div>
                  <div v-if="form.evenHeaderContent && form.evenUseCurrentTitle" class="form-row">
                    <label>内容位置</label>
                    <div class="radio-group">
                      <label><input type="radio" v-model="form.evenHeaderPosition" value="contentLeft"> 固定内容在左，一级标题在右</label>
                      <label><input type="radio" v-model="form.evenHeaderPosition" value="titleLeft"> 一级标题在左，固定内容在右</label>
                    </div>
                  </div>
                  <div class="form-flex">
                    <TemplateFormSelect label="字体" v-model="form.evenHeaderFont" :options="cnEnFonts" />
                    <TemplateFormSelect label="字号" v-model="form.evenHeaderSize" :options="fontSizes" defaultVal="小四" />
                    <TemplateFormSelect label="对齐方式" v-model="form.evenHeaderAlign" :options="alignOptionsBasic" defaultVal="center" />
                    <TemplateToggleItem label="加粗" v-model="form.evenHeaderBold" />
                    <TemplateFormInput label="上边距" v-model.number="form.evenHeaderTopMargin" unit="厘米" />
                  </div>
                </div>
              </div>
            </div>

            <div class="block-divider"></div>

            <div class="setting-block">
              <div class="block-title">页脚设置</div>
              <TemplateToggleItem label="启用页脚" v-model="form.enableFooter" />
              <div v-if="form.enableFooter" class="setting-block-group" style="margin-top:16px">
                <div v-if="!form.enableDifferentFooter">
                  <div class="form-flex">
                    <TemplateFormSelect label="字体" v-model="form.footerFont" :options="cnEnFonts" />
                    <TemplateFormSelect label="字号" v-model="form.footerSize" :options="fontSizes" defaultVal="五号" />
                    <TemplateToggleItem label="显示总页数" v-model="form.showTotalPages" />
                    <TemplateFormSelect label="页码类型" v-model="form.pageNumberType" :options="pageNumberTypeOptions" defaultVal="arabic" />
                    <TemplateFormSelect label="页码位置" v-model="form.pageNumberAlign" :options="alignOptionsBasic" defaultVal="center" />
                  </div>
                  <div class="form-flex">
                    <TemplateFormInput label="页码左侧内容" v-model="form.pageNumberPrefix" placeholder="第" />
                    <TemplateFormInput label="页码右侧内容" v-model="form.pageNumberSuffix" placeholder="页" />
                  </div>
                </div>
                <div class="even-odd-toggle-bar">
                  <TemplateToggleItem label="启用奇偶页不同页脚" v-model="form.enableDifferentFooter" />
                </div>
                <div v-if="form.enableDifferentFooter" class="setting-block-group" style="margin-top:12px; background:#f0fdfa; border-left:3px solid #14b8a6">
                  <div class="block-group-label">奇数页</div>
                  <div class="form-flex">
                    <TemplateFormSelect label="页码类型" v-model="form.oddPageNumberType" :options="pageNumberTypeOptions" defaultVal="arabic" />
                    <TemplateFormSelect label="页码位置" v-model="form.oddPageNumberAlign" :options="alignOptionsBasic" defaultVal="center" />
                    <TemplateFormInput label="页码左侧内容" v-model="form.oddPageNumberPrefix" placeholder="第" />
                    <TemplateFormInput label="页码右侧内容" v-model="form.oddPageNumberSuffix" placeholder="页" />
                  </div>
                  <div class="block-group-label">偶数页</div>
                  <div class="form-flex">
                    <TemplateFormSelect label="页码类型" v-model="form.evenPageNumberType" :options="pageNumberTypeOptions" defaultVal="arabic" />
                    <TemplateFormSelect label="页码位置" v-model="form.evenPageNumberAlign" :options="alignOptionsBasic" defaultVal="center" />
                    <TemplateFormInput label="页码左侧内容" v-model="form.evenPageNumberPrefix" placeholder="第" />
                    <TemplateFormInput label="页码右侧内容" v-model="form.evenPageNumberSuffix" placeholder="页" />
                  </div>
                </div>
              </div>
            </div>

            <div class="block-divider"></div>

            <div class="setting-block">
              <div class="block-title">前置部分页脚设置 <span class="block-title-hint">（摘要 / 目录 / 引言等）</span></div>
              <TemplateToggleItem label="启用前置部分页脚" v-model="form.enableFrontMatterFooter" :defaultVal="true" />
              <div v-if="form.enableFrontMatterFooter">
                <div class="form-flex" style="margin-top:8px">
                  <TemplateFormSelect label="页码类型" v-model="form.frontMatterPageNumberType" :options="pageNumberTypeOptions" defaultVal="roman" />
                  <TemplateFormSelect label="页码位置" v-model="form.frontMatterPageNumberAlign" :options="alignOptionsBasic" defaultVal="center" />
                  <TemplateFormSelect label="字体" v-model="form.frontMatterFooterFont" :options="cnEnFonts" />
                  <TemplateFormSelect label="字号" v-model="form.frontMatterFooterSize" :options="fontSizes" defaultVal="五号" />
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ========== 页边距设置 ========== -->
        <section v-show="activeTab === 'margins'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">页边距设置</h3>
            </div>

            <div class="setting-block">
              <div class="block-title">边距</div>
              <div class="form-flex">
                <TemplateFormInput label="上边距" v-model.number="form.topMargin" unit="厘米" />
                <TemplateFormInput label="下边距" v-model.number="form.bottomMargin" unit="厘米" />
                <TemplateFormInput label="左边距" v-model.number="form.leftMargin" unit="厘米" />
                <TemplateFormInput label="右边距" v-model.number="form.rightMargin" unit="厘米" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">纸张大小</div>
              <div class="form-row">
                <label>纸张大小</label>
                <select v-model="form.paperSize" class="ds-select" style="width:280px">
                  <optgroup v-for="group in paperSizeGroups" :key="group.label" :label="group.label">
                    <option v-for="o in group.options" :key="o.value" :value="o.value">{{ o.label }}</option>
                  </optgroup>
                </select>
              </div>
              <div v-if="form.paperSize === 'custom'" class="setting-block" style="margin-top:10px">
                <div class="block-title">自定义尺寸</div>
                <div class="form-flex">
                  <TemplateInputWithUnit label="宽度" v-model:value="form.customPaperWidth" v-model:unit="form.customPaperWidthUnit" :units="lengthUnits" />
                  <TemplateInputWithUnit label="高度" v-model:value="form.customPaperHeight" v-model:unit="form.customPaperHeightUnit" :units="lengthUnits" />
                  <div class="form-row-inner">
                    <label>方向</label>
                    <div class="radio-group">
                      <label><input type="radio" v-model="form.paperOrientation" value="portrait"> 纵向</label>
                      <label><input type="radio" v-model="form.paperOrientation" value="landscape"> 横向</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- ========== 参考文献设置 ========== -->
        <section v-show="activeTab === 'references'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">参考文献设置</h3>
            </div>

            <div class="setting-block">
              <div class="toggle-row">
                <TemplateToggleItem label="文献章节标题是否使用序号" v-model="form.ckwxXuhaoSwitch" />
                <TemplateToggleItem label="参考文献标题使用章节标题属性" v-model="form.ckwxTitleUseChapter" :defaultVal="true" />
              </div>
            </div>

            <div v-if="!form.ckwxTitleUseChapter" class="setting-block">
              <div class="block-title">自定义参考文献标题属性</div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.ckwxTitleFont" :options="allFonts" />
                <TemplateFormSelect label="字号" v-model="form.ckwxTitleSize" :options="fontSizes" defaultVal="小四" />
                <TemplateFormSelect label="对齐方式" v-model="form.ckwxTitleAlign" :options="alignOptions" defaultVal="center" />
                <TemplateToggleItem label="加粗" v-model="form.ckwxTitleBold" :defaultVal="true" />
              </div>
              <div class="form-flex">
                <TemplateInputWithUnit label="段前间距" v-model:value="form.ckwxTitleBefore" v-model:unit="form.ckwxTitleBeforeUnit" :units="spacingUnits" />
                <TemplateInputWithUnit label="段后间距" v-model:value="form.ckwxTitleAfter" v-model:unit="form.ckwxTitleAfterUnit" :units="spacingUnits" />
              </div>
            </div>

            <div class="setting-block">
              <div class="block-title">参考文献正文</div>
              <div class="form-flex">
                <TemplateFormSelect label="字体" v-model="form.ckwxFont" :options="allFonts" />
                <TemplateFormSelect label="字号" v-model="form.ckwxSize" :options="fontSizes" defaultVal="小四" />
                <TemplateFormSelect label="对齐方式" v-model="form.ckwxAlign" :options="alignOptions" defaultVal="left" />
                <TemplateSpacingGroup label="行距" v-model:rule="form.ckwxSpacingRule" v-model:value="form.ckwxSpacingValue" v-model:unit="form.ckwxSpacingRuleUnit" />
                <TemplateToggleItem label="加粗" v-model="form.ckwxBold" />
              </div>
              <div class="form-flex">
                <TemplateInputWithUnit label="首行缩进" v-model:value="form.ckwxFirstLineIndent" v-model:unit="form.ckwxIndentUnit" :units="indentUnits" />
                <TemplateInputWithUnit label="左缩进" v-model:value="form.ckwxLeftIndent" v-model:unit="form.ckwxLeftIndentUnit" :units="indentUnits" />
                <TemplateInputWithUnit label="段前间距" v-model:value="form.ckwxBefore" v-model:unit="form.ckwxBeforeUnit" :units="spacingUnits" />
                <TemplateInputWithUnit label="段后间距" v-model:value="form.ckwxAfter" v-model:unit="form.ckwxAfterUnit" :units="spacingUnits" />
              </div>
            </div>
          </div>
        </section>

        <!-- ========== 其它设置 ========== -->
        <section v-show="activeTab === 'others'" class="tab-panel">
          <div class="section-card">
            <div class="section-head">
              <h3 class="section-title">其它设置</h3>
            </div>

            <div class="setting-block">
              <div class="block-title">章节</div>
              <TemplateToggleItem label="一级章节之间需要换页" v-model="form.title1Switch" :defaultVal="true" />
            </div>

            <div class="form-flex">
              <div class="setting-block" style="margin:0; padding:0; border:none;">
                <TemplateToggleItem label="启用引言" v-model="form.yinyanSetting" />
              </div>
              <div class="setting-block" style="margin:0; padding:0; border:none;">
                <TemplateToggleItem label="启用总结" v-model="form.zongjieSetting" />
              </div>
              <div class="setting-block" style="margin:0; padding:0; border:none;">
                <TemplateToggleItem label="启用致谢" v-model="form.zhixieSetting" />
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <ClientOnly>
      <Teleport to="body">
        <div v-if="showSaveModal" class="modal-overlay" @click.self="showSaveModal = false">
          <div class="modal-dialog">
            <div class="modal-header">
              <div class="modal-title">
                <svg class="modal-title-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                  <polyline points="17 21 17 13 7 13 7 21"></polyline>
                  <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                <span>{{ isEditMode ? '编辑模板' : '保存模板' }}</span>
              </div>
              <button class="modal-close" @click="showSaveModal = false">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
            <div class="modal-body">
              <div class="modal-desc">将当前排版参数保存为模板，方便下次快速使用</div>
              <div class="modal-field">
                <label>模板名称 <span class="required">*</span></label>
                <input type="text" v-model="templateName" class="ds-input" placeholder="请输入模板名称，如：本科毕业论文模板">
              </div>
              <div class="modal-field">
                <label>模板封面</label>
                <div class="modal-logo-upload" @click="triggerLogoUpload">
                  <input ref="logoInput" type="file" accept="image/*" style="display:none" @change="onLogoChange">
                  <img v-if="templateLogo" :src="templateLogo" class="modal-logo-preview">
                  <div v-else class="modal-logo-placeholder">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <span>点击上传封面图片</span>
                    <span class="modal-logo-hint">建议 800×600px，最大 2MB</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-actions">
              <button class="ds-btn ds-btn-secondary" @click="showSaveModal = false">取消</button>
              <button class="ds-btn ds-btn-primary" @click="confirmSave" :disabled="saving">
                <span v-if="saving" class="btn-loading"></span>
                {{ saving ? '保存中...' : (isEditMode ? '确认更新' : '确认保存') }}
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </ClientOnly>

    <!-- 排版演示加载动画 -->
    <ClientOnly>
      <Teleport to="body">
        <Transition name="demo-fade">
          <div v-if="demonstrating" class="demo-loading-overlay">
            <div class="demo-loading-card">
              <div class="demo-doc-animation">
                <svg class="demo-doc-svg" viewBox="0 0 80 100" xmlns="http://www.w3.org/2000/svg">
                  <rect class="demo-doc-page" x="8" y="6" width="56" height="78" rx="4" fill="#fff" stroke="#14b8a6" stroke-width="2"/>
                  <rect class="demo-doc-line demo-doc-line-1" x="16" y="20" width="32" height="3" rx="1.5" fill="#14b8a6" opacity="0.6"/>
                  <rect class="demo-doc-line demo-doc-line-2" x="16" y="28" width="40" height="3" rx="1.5" fill="#94a3b8" opacity="0.5"/>
                  <rect class="demo-doc-line demo-doc-line-3" x="16" y="36" width="36" height="3" rx="1.5" fill="#94a3b8" opacity="0.5"/>
                  <rect class="demo-doc-line demo-doc-line-4" x="16" y="44" width="38" height="3" rx="1.5" fill="#94a3b8" opacity="0.5"/>
                  <rect class="demo-doc-line demo-doc-line-5" x="16" y="52" width="30" height="3" rx="1.5" fill="#94a3b8" opacity="0.5"/>
                  <rect class="demo-doc-line demo-doc-line-6" x="16" y="60" width="34" height="3" rx="1.5" fill="#94a3b8" opacity="0.5"/>
                  <rect class="demo-doc-line demo-doc-line-7" x="16" y="68" width="28" height="3" rx="1.5" fill="#94a3b8" opacity="0.5"/>
                </svg>
                <div class="demo-pen-animation">
                  <svg viewBox="0 0 24 24" fill="none" stroke="#14b8a6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                    <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                    <path d="M2 2l7.586 7.586"/>
                    <circle cx="11" cy="11" r="2"/>
                  </svg>
                </div>
                <div class="demo-ring-pulse"></div>
              </div>
              <div class="demo-loading-title">正在排版演示</div>
              <div class="demo-loading-status">{{ demoStatusText }}</div>
              <div class="demo-progress-track">
                <div class="demo-progress-fill" :style="{ width: demoProgress + '%' }"></div>
              </div>
              <div class="demo-progress-percent">{{ demoProgress }}%</div>
            </div>
          </div>
        </Transition>

        <div v-if="showPreviewModal" class="modal-overlay preview-modal-overlay" @click.self="showPreviewModal = false">
          <div class="modal-dialog preview-dialog">
            <div class="preview-accent-bar"></div>
            <button class="modal-close preview-close" @click="showPreviewModal = false">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="modal-body preview-body">
              <div class="preview-result">
                <div class="preview-success-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                  </svg>
                </div>
                <div class="preview-message">排版文档已成功生成</div>
                <div class="preview-desc">已使用预置内容和当前模板参数生成排版文档，点击下方按钮下载查看实际排版效果</div>
                <div class="preview-actions">
                  <a :href="previewUrl" target="_blank" rel="noopener" class="preview-download-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                      <polyline points="7 10 12 15 17 10"></polyline>
                      <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>下载文档</span>
                  </a>
                  <button class="preview-close-btn" @click="showPreviewModal = false">关闭</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Teleport>
    </ClientOnly>

  </div>
</template>

<script setup>
import { ref, reactive, watch, computed, onMounted } from 'vue'

definePageMeta({ layout: 'console' })

const api = useApi()
const toast = useToast()
const route = useRoute()
const router = useRouter()

const activeTab = ref('cover')
const showSaveModal = ref(false)
const templateName = ref('')
const templateLogo = ref('')
const logoInput = ref(null)
const uploadingLogo = ref(false)
const saving = ref(false)
const demonstrating = ref(false)
const showPreviewModal = ref(false)
const previewUrl = ref('')
const demoStatusText = ref('正在初始化...')
const demoProgress = ref(0)
let _demoTimer = null
let _demoProgressTimer = null
const fileInput = ref(null)
const isDragging = ref(false)
const selectedFile = ref(null)
const coverOssUrl = ref('')
const uploadingCover = ref(false)

// 编辑模式相关状态
const templateId = ref(0)        // > 0 表示编辑已有模板
const loadingTemplate = ref(false)
const isEditMode = computed(() => templateId.value > 0)
const originalTemplateName = ref('')  // 编辑模式下保存原始模板名称
const originalTemplateLogo = ref('')  // 编辑模式下保存原始封面图

const tabs = [
  { key: 'cover', label: '封面设置', svg: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>' },
  { key: 'abstract', label: '摘要和关键词', svg: '<line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="18" x2="14" y2="18"/>' },
  { key: 'catalog', label: '目录设置', svg: '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="3.5" cy="6" r="1.5"/><circle cx="3.5" cy="12" r="1.5"/><circle cx="3.5" cy="18" r="1.5"/>' },
  { key: 'title', label: '标题设置', svg: '<path d="M6 4v16"/><path d="M18 4v16"/><path d="M6 9h12"/><path d="M6 15h12"/>' },
  { key: 'content', label: '正文设置', svg: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="14" y2="17"/>' },
  { key: 'headerFooter', label: '页眉页脚', svg: '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/>' },
  { key: 'margins', label: '页边距设置', svg: '<path d="M3 3h18v18H3z"/><path d="M7 7h10v10H7z"/>' },
  { key: 'references', label: '参考文献', svg: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>' },
  { key: 'others', label: '其它设置', svg: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>' },
]

// 表单参数值（与后端排版系统严格对应，请勿随意修改）
const form = reactive({
  enableEnglish: true,
  specialSetting: false,
  abstractTitleTopTitle: '',
  specialTitleFont: '宋体',
  specialTitleSize: '小四',
  specialTitleSpacingRule: '1.5line',
  specialTitleSpacingValue: 1.2,
  specialTitleSpacingRuleUnit: 'line',
  specialTitleBefore: 0,
  specialTitleBeforeUnit: 'line',
  specialTitleAfter: 0,
  specialTitleAfterUnit: 'line',
  abstractTitleLine: 1,
  abstractContentLine: 1,
  abstractKeywordsNum: 4,
  abstractKeywordsSymbol: ',',
  abstractKeywordsIntervalNum: 2,
  cnAbstractTitleFont: '宋体',
  cnAbstractTitleSize: '小四',
  cnAbstractTitleAlign: 'center',
  cnAbstractTitleInterval: 0,
  cnAbstractTitleBold: false,
  cnAbstractFirstLineIndent: 2,
  cnAbstractIndentUnit: 'char',
  cnAbstractFont: '宋体',
  cnAbstractSize: '小四',
  cnAbstractLineSpacingRule: '1.5line',
  cnAbstractLineSpacingValue: 1.5,
  cnAbstractLineSpacingRuleUnit: 'pt',
  cnAbstractAlign: 'left',
  cnAbstractKeywordsFont: '黑体',
  cnAbstractKeywordsSize: '小四',
  cnAbstractKeywordsBold: true,
  cnAbstractKeywordsContentFont: '宋体',
  cnAbstractKeywordsContentSize: '小四',
  enAbstractTitleFont: 'Times New Roman',
  enAbstractTitleSize: '小四',
  enAbstractTitleAlign: 'center',
  enAbstractTitleInterval: 0,
  enAbstractTitleBold: false,
  enAbstractFirstLineIndent: 2,
  enAbstractIndentUnit: 'char',
  enAbstractFont: 'Times New Roman',
  enAbstractSize: '小四',
  enAbstractLineSpacingRule: '1.5line',
  enAbstractLineSpacingValue: 1.5,
  enAbstractLineSpacingRuleUnit: 'pt',
  enAbstractAlign: 'left',
  enAbstractKeywordsFont: 'Times New Roman',
  enAbstractKeywordsSize: '小四',
  enAbstractKeywordsBold: true,
  enAbstractKeywordsContentFont: 'Times New Roman',
  enAbstractKeywordsContentSize: '小四',
  switchThird: true,
  showTocTitle: true,
  tocTitleFont: '黑体',
  tocTitleSize: '小三',
  tocTitleAlign: 'center',
  tocTitleLineSpacingRule: '1.5line',
  tocTitleLineSpacingValue: 1.5,
  tocTitleSpaceBefore: 0,
  tocTitleSpaceAfter: 0,
  tocFont: '宋体',
  tocSize: '小四',
  tocLineSpacingRule: '1.5line',
  tocLineSpacingValue: 1.5,
  tocLineSpacingRuleUnit: 'pt',
  tocLeader: 'dot',
  tocAlign: 'justify',
  title1NumberFormat: '0',
  title1Separator: '0',
  title1Font: '黑体',
  title1Size: '三号',
  title1Align: 'center',
  title1Bold: true,
  title1LineSpacingRule: '1.5line',
  title1LineSpacingValue: 1.5,
  title1LineSpacingRuleUnit: 'pt',
  title1SpaceBefore: 0,
  title1SpaceBeforeUnit: 'line',
  title1SpaceAfter: 0,
  title1SpaceAfterUnit: 'line',
  title2NumberFormat: '0',
  title2Separator: '1',
  title2Font: '黑体',
  title2Size: '四号',
  title2Align: 'left',
  title2Bold: true,
  title2LineSpacingRule: '1.5line',
  title2LineSpacingValue: 1.5,
  title2LineSpacingRuleUnit: 'pt',
  title2SpaceBefore: 0,
  title2SpaceBeforeUnit: 'line',
  title2SpaceAfter: 0,
  title2SpaceAfterUnit: 'line',
  title3NumberFormat: '0',
  title3Separator: '1',
  title3Font: '黑体',
  title3Size: '小四',
  title3Align: 'left',
  title3Bold: true,
  title3LineSpacingRule: '1.5line',
  title3LineSpacingValue: 1.5,
  title3LineSpacingRuleUnit: 'pt',
  title3SpaceBefore: 0,
  title3SpaceBeforeUnit: 'line',
  title3SpaceAfter: 0,
  title3SpaceAfterUnit: 'line',
  contentNumEnSwitch: true,
  contentNumEnFont: 'Times New Roman',
  contentNumEnSize: '小四',
  contentFont: '宋体',
  contentSize: '小四',
  paragraphAlign: 'left',
  firstLineIndent: 2,
  indentUnit: 'char',
  leftIndent: 0,
  leftIndentUnit: 'pt',
  rightIndent: 0,
  rightIndentUnit: 'pt',
  lineSpacingRule: '1.5line',
  lineSpacingValue: 1.5,
  lineSpacingRuleUnit: 'pt',
  spaceBefore: 0,
  spaceBeforeUnit: 'line',
  spaceAfter: 0,
  spaceAfterUnit: 'line',
  enableDifferentHeader: false,
  oddHeaderContent: '',
  oddUseCurrentTitle: false,
  oddHeaderPosition: 'contentLeft',
  oddHeaderFont: '宋体',
  oddHeaderSize: '小四',
  oddHeaderAlign: 'center',
  oddHeaderBold: false,
  oddHeaderTopMargin: 1.5,
  evenHeaderContent: '',
  evenUseCurrentTitle: false,
  evenHeaderPosition: 'contentLeft',
  evenHeaderFont: '宋体',
  evenHeaderSize: '小四',
  evenHeaderAlign: 'center',
  evenHeaderBold: false,
  evenHeaderTopMargin: 1.5,
  enableHeader: false,
  headerContent: '',
  useCurrentTitle: false,
  headerPosition: 'contentLeft',
  headerFont: '宋体',
  headerSize: '小四',
  headerAlign: 'center',
  headerBold: false,
  headerBorderStyle: 'single',
  headerBorderColor: 'auto',
  headerBorderSize: '2',
  headerBorderSpace: 1,
  headerBorderShadow: false,
  headerTopMargin: 1.5,
  headerBottomMargin: 1.5,
  enableFooter: false,
  footerFont: '宋体',
  footerSize: '五号',
  showTotalPages: false,
  pageNumberType: 'arabic',
  pageNumberAlign: 'center',
  pageNumberPrefix: '',
  pageNumberSuffix: '',
  enableDifferentFooter: false,
  oddPageNumberType: 'arabic',
  oddPageNumberPrefix: '',
  oddPageNumberSuffix: '',
  oddPageNumberAlign: 'center',
  evenPageNumberType: 'arabic',
  evenPageNumberPrefix: '',
  evenPageNumberSuffix: '',
  evenPageNumberAlign: 'center',
  enableFrontMatterFooter: true,
  frontMatterPageNumberType: 'roman',
  frontMatterPageNumberAlign: 'center',
  frontMatterFooterFont: '宋体',
  frontMatterFooterSize: '五号',
  topMargin: 3.0,
  bottomMargin: 2.5,
  leftMargin: 3.0,
  rightMargin: 2.5,
  paperSize: 'A4',
  customPaperWidth: 210,
  customPaperWidthUnit: 'mm',
  customPaperHeight: 297,
  customPaperHeightUnit: 'mm',
  paperOrientation: 'portrait',
  ckwxXuhaoSwitch: false,
  ckwxTitleUseChapter: true,
  ckwxTitleFont: '宋体',
  ckwxTitleSize: '小四',
  ckwxTitleAlign: 'center',
  ckwxTitleBold: true,
  ckwxTitleBefore: 0,
  ckwxTitleBeforeUnit: 'line',
  ckwxTitleAfter: 0,
  ckwxTitleAfterUnit: 'line',
  ckwxFont: '宋体',
  ckwxSize: '小四',
  ckwxAlign: 'left',
  ckwxBold: false,
  ckwxSpacingRule: '1.5line',
  ckwxSpacingValue: 1.5,
  ckwxSpacingRuleUnit: 'pt',
  ckwxBefore: 0,
  ckwxBeforeUnit: 'line',
  ckwxAfter: 0,
  ckwxAfterUnit: 'line',
  ckwxFirstLineIndent: 0,
  ckwxIndentUnit: 'pt',
  ckwxLeftIndent: 0,
  ckwxLeftIndentUnit: 'pt',
  title1Switch: true,
  yinyanSetting: false,
  zongjieSetting: false,
  zhixieSetting: false,
})

watch(() => form.enableDifferentHeader, (val) => {
  if (val) form.enableHeader = true
})

// 保存 form 初始快照,用于从编辑模式切回创建模式时重置
const _initialFormSnapshot = JSON.parse(JSON.stringify(form))

// 重置为创建模式状态
function resetToCreateMode() {
  templateId.value = 0
  originalTemplateName.value = ''
  originalTemplateLogo.value = ''
  templateName.value = ''
  templateLogo.value = ''
  coverOssUrl.value = ''
  selectedFile.value = null
  activeTab.value = 'cover'
  // 恢复表单初始值
  Object.assign(form, JSON.parse(JSON.stringify(_initialFormSnapshot)))
}

// 监听路由 id 变化,处理编辑/创建模式切换
watch(() => route.query.id, (newId, oldId) => {
  const newIdNum = Number(newId) || 0
  const oldIdNum = Number(oldId) || 0
  if (newIdNum === oldIdNum) return
  if (newIdNum > 0) {
    // 切到编辑模式:同步设置避免闪烁,再加载模板
    templateId.value = newIdNum
    loadTemplate(newIdNum)
  } else {
    // 切到创建模式:重置状态
    resetToCreateMode()
  }
})

const titleLevels = [
  {
    key: '1', label: '一级标题',
    numberFormatKey: 'title1NumberFormat', separatorKey: 'title1Separator', fontKey: 'title1Font', sizeKey: 'title1Size',
    alignKey: 'title1Align', boldKey: 'title1Bold',
    lineSpacingRuleKey: 'title1LineSpacingRule', lineSpacingValueKey: 'title1LineSpacingValue', lineSpacingUnitKey: 'title1LineSpacingRuleUnit',
    spaceBeforeKey: 'title1SpaceBefore', spaceBeforeUnitKey: 'title1SpaceBeforeUnit',
    spaceAfterKey: 'title1SpaceAfter', spaceAfterUnitKey: 'title1SpaceAfterUnit',
    numberFormats: [
      { value: '0', label: '第一章、第二章、第三章' }, { value: '1', label: '第1章、第2章、第3章' },
      { value: '2', label: '一、二、三' }, { value: '3', label: '（一）、（二）、（三）' },
      { value: '4', label: '1、2、3' },
    ],
    defaultNumberFormat: '0', defaultSeparator: '0', defaultFont: '黑体', defaultSize: '三号',
    defaultAlign: 'center',
    alignOpts: [{ value: 'left', label: '左对齐' }, { value: 'center', label: '居中' }, { value: 'right', label: '右对齐' }],
  },
  {
    key: '2', label: '二级标题',
    numberFormatKey: 'title2NumberFormat', separatorKey: 'title2Separator', fontKey: 'title2Font', sizeKey: 'title2Size',
    alignKey: 'title2Align', boldKey: 'title2Bold',
    lineSpacingRuleKey: 'title2LineSpacingRule', lineSpacingValueKey: 'title2LineSpacingValue', lineSpacingUnitKey: 'title2LineSpacingRuleUnit',
    spaceBeforeKey: 'title2SpaceBefore', spaceBeforeUnitKey: 'title2SpaceBeforeUnit',
    spaceAfterKey: 'title2SpaceAfter', spaceAfterUnitKey: 'title2SpaceAfterUnit',
    numberFormats: [
      { value: '0', label: '一、二、三' }, { value: '1', label: '（一）、（二）、（三）' },
      { value: '2', label: '（1）、（2）、（3）' }, { value: '3', label: '1.1、1.2、1.3' }, { value: '4', label: '1、2、3' },
    ],
    defaultNumberFormat: '0', defaultSeparator: '1', defaultFont: '黑体', defaultSize: '四号',
    defaultAlign: 'left',
    alignOpts: [{ value: 'left', label: '左对齐' }, { value: 'center', label: '居中' }],
  },
  {
    key: '3', label: '三级标题',
    numberFormatKey: 'title3NumberFormat', separatorKey: 'title3Separator', fontKey: 'title3Font', sizeKey: 'title3Size',
    alignKey: 'title3Align', boldKey: 'title3Bold',
    lineSpacingRuleKey: 'title3LineSpacingRule', lineSpacingValueKey: 'title3LineSpacingValue', lineSpacingUnitKey: 'title3LineSpacingRuleUnit',
    spaceBeforeKey: 'title3SpaceBefore', spaceBeforeUnitKey: 'title3SpaceBeforeUnit',
    spaceAfterKey: 'title3SpaceAfter', spaceAfterUnitKey: 'title3SpaceAfterUnit',
    numberFormats: [
      { value: '0', label: '1、2、3' }, { value: '1', label: '（1）、（2）、（3）' },
      { value: '2', label: '1.1.1、1.1.2、1.1.3' }, { value: '3', label: '（一）、（二）、（三）' },
    ],
    defaultNumberFormat: '0', defaultSeparator: '1', defaultFont: '黑体', defaultSize: '小四',
    defaultAlign: 'left',
    alignOpts: [{ value: 'left', label: '左对齐' }, { value: 'center', label: '居中' }],
  },
]

const chineseFonts = [
  { value: '宋体', label: '宋体' }, { value: '黑体', label: '黑体' }, { value: '楷体', label: '楷体' },
  { value: '仿宋', label: '仿宋' }, { value: '华文宋体', label: '华文宋体' }, { value: '华文楷体', label: '华文楷体' },
  { value: '华文仿宋', label: '华文仿宋' }, { value: '华文中宋', label: '华文中宋' },
  { value: '华文彩云', label: '华文彩云' }, { value: '华文新魏', label: '华文新魏' },
  { value: '华文隶书', label: '华文隶书' }, { value: '华文行楷', label: '华文行楷' },
  { value: '方正舒体', label: '方正舒体' }, { value: '方正姚体', label: '方正姚体' },
  { value: '微软雅黑', label: '微软雅黑' }, { value: '等线', label: '等线' },
]

const englishFonts = [
  { value: 'Arial', label: 'Arial' }, { value: 'Arial Black', label: 'Arial Black' },
  { value: 'Calibri', label: 'Calibri' }, { value: 'Cambria', label: 'Cambria' },
  { value: 'Comic Sans MS', label: 'Comic Sans MS' }, { value: 'Courier New', label: 'Courier New' },
  { value: 'Georgia', label: 'Georgia' }, { value: 'Impact', label: 'Impact' },
  { value: 'Times New Roman', label: 'Times New Roman' }, { value: 'Trebuchet MS', label: 'Trebuchet MS' },
  { value: 'Verdana', label: 'Verdana' },
]

const allFonts = [...chineseFonts, ...englishFonts]
const cnEnFonts = [
  { value: '宋体', label: '宋体' }, { value: '黑体', label: '黑体' }, { value: '楷体', label: '楷体' },
  { value: '仿宋', label: '仿宋' }, { value: '微软雅黑', label: '微软雅黑' },
  { value: 'Times New Roman', label: 'Times New Roman' }, { value: 'Arial', label: 'Arial' },
  { value: 'Calibri', label: 'Calibri' },
]

const fontSizes = [
  { value: '初号', label: '初号' }, { value: '小初', label: '小初' }, { value: '一号', label: '一号' },
  { value: '小一', label: '小一' }, { value: '二号', label: '二号' }, { value: '小二', label: '小二' },
  { value: '三号', label: '三号' }, { value: '小三', label: '小三' }, { value: '四号', label: '四号' },
  { value: '小四', label: '小四' }, { value: '五号', label: '五号' }, { value: '小五', label: '小五' },
  { value: '六号', label: '六号' }, { value: '小六', label: '小六' }, { value: '七号', label: '七号' }, { value: '八号', label: '八号' },
  { value: '5', label: '5' }, { value: '5.5', label: '5.5' }, { value: '6.5', label: '6.5' },
  { value: '7.5', label: '7.5' }, { value: '8', label: '8' }, { value: '9', label: '9' },
  { value: '10', label: '10' }, { value: '10.5', label: '10.5' }, { value: '11', label: '11' },
  { value: '12', label: '12' }, { value: '14', label: '14' }, { value: '16', label: '16' },
  { value: '18', label: '18' }, { value: '20', label: '20' }, { value: '22', label: '22' },
  { value: '24', label: '24' }, { value: '26', label: '26' }, { value: '28', label: '28' },
  { value: '36', label: '36' }, { value: '48', label: '48' }, { value: '72', label: '72' },
]

const alignOptions = [
  { value: 'left', label: '左对齐' }, { value: 'center', label: '居中' },
  { value: 'justify', label: '两端对齐' }, { value: 'right', label: '右对齐' },
]

const alignOptionsBasic = [
  { value: 'left', label: '左对齐' }, { value: 'center', label: '居中' }, { value: 'right', label: '右对齐' },
]

const indentUnits = [
  { value: 'char', label: '字符' }, { value: 'pt', label: '磅' },
  { value: 'inch', label: '英寸' }, { value: 'cm', label: '厘米' }, { value: 'mm', label: '毫米' },
]

const spacingUnits = [
  { value: 'line', label: '行' }, { value: 'pt', label: '磅' }, { value: 'cm', label: '厘米' },
]

const lengthUnits = [
  { value: 'mm', label: '毫米' }, { value: 'cm', label: '厘米' }, { value: 'inch', label: '英寸' },
]

const separatorOptions = [
  { value: '0', label: '无' }, { value: '1', label: '、' },
  { value: '2', label: '句点(.)' }, { value: '3', label: '中点(·)' },
]

const leaderOptions = [
  { value: 'dot', label: '点线 (....)' }, { value: 'underscore', label: '下划线 (____)' },
  { value: 'hyphen', label: '虚线 (----)' }, { value: 'none', label: '无' },
]

const borderStyleOptions = [
  { value: 'single', label: '单线边框' }, { value: 'double', label: '双线边框' },
  { value: 'dot', label: '点线边框' }, { value: 'dash', label: '虚线边框' },
  { value: 'dashDot', label: '点划线边框' }, { value: 'dashDotDot', label: '点点划线边框' },
  { value: 'dotDash', label: '点划点线边框' }, { value: 'wave', label: '波浪线边框' },
  { value: 'thick', label: '粗线边框' }, { value: 'doubleWave', label: '双波浪线边框' },
  { value: 'none', label: '无边框' },
]

const borderColorOptions = [
  { value: 'auto', label: '自动' }, { value: 'black', label: '黑色' },
  { value: 'red', label: '红色' }, { value: 'blue', label: '蓝色' },
  { value: 'green', label: '绿色' }, { value: 'gray', label: '灰色' },
]

const borderSizeOptions = [
  { value: '2', label: '0.5磅' }, { value: '4', label: '1磅' },
  { value: '6', label: '1.5磅' }, { value: '8', label: '2磅' }, { value: '12', label: '3磅' },
]

const pageNumberTypeOptions = [
  { value: 'arabic', label: '阿拉伯数字 (1, 2, 3...)' },
  { value: 'roman', label: '罗马数字 (I, II, III...)' },
  { value: 'roman_lower', label: '小写罗马数字 (i, ii, iii...)' },
  { value: 'letter', label: '英文字母 (A, B, C...)' },
  { value: 'letter_lower', label: '小写字母 (a, b, c...)' },
  { value: 'chinese', label: '中文数字 (一, 二, 三...)' },
  { value: 'chinese_capital', label: '中文大写数字 (壹, 贰, 叁...)' },
]

const paperSizeGroups = [
  {
    label: 'A 系列',
    options: [
      { value: 'A0', label: 'A0 (841x1189mm)' }, { value: 'A1', label: 'A1 (594x841mm)' },
      { value: 'A2', label: 'A2 (420x594mm)' }, { value: 'A3', label: 'A3 (297x420mm)' },
      { value: 'A4', label: 'A4 (210x297mm)' }, { value: 'A5', label: 'A5 (148x210mm)' },
      { value: 'A6', label: 'A6 (105x148mm)' }, { value: 'A7', label: 'A7 (74x105mm)' },
      { value: 'A8', label: 'A8 (52x74mm)' }, { value: 'A9', label: 'A9 (37x52mm)' },
    ],
  },
  {
    label: 'B 系列',
    options: [
      { value: 'B0', label: 'B0 (1000x1414mm)' }, { value: 'B1', label: 'B1 (707x1000mm)' },
      { value: 'B2', label: 'B2 (500x707mm)' }, { value: 'B3', label: 'B3 (353x500mm)' },
      { value: 'B4', label: 'B4 (250x353mm)' }, { value: 'B5', label: 'B5 (176x250mm)' },
    ],
  },
  {
    label: '信纸 / 中文标准',
    options: [
      { value: 'letter', label: 'Letter (216x279mm)' }, { value: 'legal', label: 'Legal (216x356mm)' },
      { value: '16k', label: '16开 (184x260mm)' }, { value: 'big16k', label: '大16开 (210x297mm)' },
      { value: '32k', label: '32开 (130x184mm)' },
    ],
  },
  {
    label: '其它',
    options: [
      { value: 'tabloid', label: 'Tabloid (279x432mm)' },
      { value: 'custom', label: '自定义纸张大小' },
    ],
  },
]

// collectSettings 输出结构（与后端排版系统严格对应，请勿修改）
function collectSettings() {
  const boolToStr = (v) => v ? '1' : '0'
  const str = (v) => String(v ?? '')
  const makeTitle = (prefix) => ({
    numberFormat: str(form[`${prefix}NumberFormat`]),
    separator: str(form[`${prefix}Separator`]),
    topline: '0',
    bottomline: '0',
    font: form[`${prefix}Font`],
    size: form[`${prefix}Size`],
    align: form[`${prefix}Align`],
    bold: form[`${prefix}Bold`],
    italic: false,
    lineSpacing: {
      rule: form[`${prefix}LineSpacingRule`],
      value: str(form[`${prefix}LineSpacingValue`]),
      unit: form[`${prefix}LineSpacingRuleUnit`],
    },
    Before: {
      value: str(form[`${prefix}SpaceBefore`]),
      type: form[`${prefix}SpaceBeforeUnit`],
    },
    After: {
      value: str(form[`${prefix}SpaceAfter`]),
      type: form[`${prefix}SpaceAfterUnit`],
    },
  })

  const paperSize = { size: form.paperSize, orientation: form.paperOrientation, unit: 'mm' }
  const paperSizes = {
    A4: { width: 210, height: 297 },
    A3: { width: 297, height: 420 },
    A5: { width: 148, height: 210 },
    B4: { width: 250, height: 353 },
    B5: { width: 176, height: 250 },
    letter: { width: 216, height: 279 },
  }
  if (form.paperSize === 'custom') {
    paperSize.width = form.customPaperWidth
    paperSize.widthUnit = form.customPaperWidthUnit
    paperSize.height = form.customPaperHeight
    paperSize.heightUnit = form.customPaperHeightUnit
  } else if (paperSizes[form.paperSize]) {
    paperSize.width = paperSizes[form.paperSize].width
    paperSize.height = paperSizes[form.paperSize].height
  }

  return {
    cover: {
      documentUrl: coverOssUrl.value || '',
    },
    abstract: {
      enableEnglish: boolToStr(form.enableEnglish),
      cnAbstract: {
        firstLineIndent: str(form.cnAbstractFirstLineIndent),
        firstLineIndentUnit: form.cnAbstractIndentUnit,
        font: form.cnAbstractFont,
        size: form.cnAbstractSize,
        align: form.cnAbstractAlign,
        lineSpacing: {
          rule: form.cnAbstractLineSpacingRule,
          value: str(form.cnAbstractLineSpacingValue),
          unit: form.cnAbstractLineSpacingRuleUnit,
        },
        cnAbstractTitleFont: form.cnAbstractTitleFont,
        cnAbstractTitleSize: form.cnAbstractTitleSize,
        cnAbstractTitleAlign: form.cnAbstractTitleAlign,
        cnAbstractTitleInterval: str(form.cnAbstractTitleInterval),
        cnAbstractTitleBold: form.cnAbstractTitleBold,
      },
      keywords: {
        AbstractKeywordsNum: str(form.abstractKeywordsNum),
        AbstractKeywordsSymbol: form.abstractKeywordsSymbol,
        AbstractKeywordsIntervalNum: str(form.abstractKeywordsIntervalNum),
      },
      cnAbstractkeywords: {
        cnAbstractKeywordsFont: form.cnAbstractKeywordsFont,
        cnAbstractKeywordsSize: form.cnAbstractKeywordsSize,
        cnAbstractKeywordsBold: form.cnAbstractKeywordsBold,
        cnAbstractKeywordsContentFont: form.cnAbstractKeywordsContentFont,
        cnAbstractKeywordsContentSize: form.cnAbstractKeywordsContentSize,
      },
      enAbstract: {
        firstLineIndent: str(form.enAbstractFirstLineIndent),
        firstLineIndentUnit: form.enAbstractIndentUnit,
        font: form.enAbstractFont,
        size: form.enAbstractSize,
        align: form.enAbstractAlign,
        lineSpacing: {
          rule: form.enAbstractLineSpacingRule,
          value: str(form.enAbstractLineSpacingValue),
          unit: form.enAbstractLineSpacingRuleUnit,
        },
        enAbstractTitleFont: form.enAbstractTitleFont,
        enAbstractTitleSize: form.enAbstractTitleSize,
        enAbstractTitleAlign: form.enAbstractTitleAlign,
        enAbstractTitleInterval: str(form.enAbstractTitleInterval),
        enAbstractTitleBold: form.enAbstractTitleBold,
      },
      enAbstractkeywords: {
        enAbstractKeywordsFont: form.enAbstractKeywordsFont,
        enAbstractKeywordsSize: form.enAbstractKeywordsSize,
        enAbstractKeywordsBold: form.enAbstractKeywordsBold,
        enAbstractKeywordsContentFont: form.enAbstractKeywordsContentFont,
        enAbstractKeywordsContentSize: form.enAbstractKeywordsContentSize,
      },
      SpecialSettingSwitch: boolToStr(form.specialSetting),
      SpecialSetting: {
        AbstractTitletopTitle: form.abstractTitleTopTitle,
        Special_settingTitleFont: form.specialTitleFont,
        Special_settingTitleSize: form.specialTitleSize,
        lineSpacing: {
          rule: form.specialTitleSpacingRule,
          value: str(form.specialTitleSpacingValue),
        },
        Before: {
          value: str(form.specialTitleBefore),
          type: form.specialTitleBeforeUnit,
        },
        After: {
          value: str(form.specialTitleAfter),
          type: form.specialTitleAfterUnit,
        },
        TitleAndContent: str(form.abstractTitleLine),
        ContentAndKeywords: str(form.abstractContentLine),
      },
    },
    titles: {
      title1: makeTitle('title1'),
      title2: makeTitle('title2'),
      title3: makeTitle('title3'),
    },
    content: {
      contentNumEnSwitch: boolToStr(form.contentNumEnSwitch),
      contentNumEnFont: form.contentNumEnFont,
      contentNumEnSize: form.contentNumEnSize,
      font: form.contentFont,
      size: form.contentSize,
      paragraph: {
        align: form.paragraphAlign,
        firstLineIndent: str(form.firstLineIndent),
        firstLineIndentUnit: form.indentUnit,
        leftIndent: str(form.leftIndent),
        leftIndentUnit: form.leftIndentUnit,
        rightIndent: str(form.rightIndent),
        rightIndentUnit: form.rightIndentUnit,
        lineSpacing: {
          rule: form.lineSpacingRule,
          value: str(form.lineSpacingValue),
          unit: form.lineSpacingRuleUnit,
        },
        Before: {
          value: str(form.spaceBefore),
          type: form.spaceBeforeUnit,
        },
        After: {
          value: str(form.spaceAfter),
          type: form.spaceAfterUnit,
        },
      },
    },
    toc: {
      switchThird: boolToStr(form.switchThird),
      showTitle: boolToStr(form.showTocTitle),
      titleSettings: {
        font: form.tocTitleFont,
        size: form.tocTitleSize,
        align: form.tocTitleAlign,
        lineSpacing: {
          rule: form.tocTitleLineSpacingRule,
          value: str(form.tocTitleLineSpacingValue),
        },
        spaceBefore: str(form.tocTitleSpaceBefore),
        spaceAfter: str(form.tocTitleSpaceAfter),
        Before: { value: str(form.tocTitleSpaceBefore), type: 'pt' },
        After: { value: str(form.tocTitleSpaceAfter), type: 'pt' },
      },
      tocContent: {
        font: form.tocFont,
        fontAscii: 'Times New Roman',
        size: form.tocSize,
        lineSpacing: {
          rule: form.tocLineSpacingRule,
          value: str(form.tocLineSpacingValue),
          unit: form.tocLineSpacingRuleUnit,
        },
        leader: form.tocLeader,
        align: 'justify',
      },
    },
    header: {
      enabled: boolToStr(form.enableHeader),
      differentPages: boolToStr(form.enableDifferentHeader),
      single: {
        content: form.headerContent,
        useCurrentTitle: form.useCurrentTitle,
        position: form.headerPosition,
        font: form.headerFont,
        size: form.headerSize,
        align: form.headerAlign,
        bold: form.headerBold,
        margins: { top: form.headerTopMargin },
      },
      odd: {
        content: form.oddHeaderContent,
        useCurrentTitle: form.oddUseCurrentTitle,
        position: form.oddHeaderPosition,
        font: form.oddHeaderFont,
        size: form.oddHeaderSize,
        align: form.oddHeaderAlign,
        bold: form.oddHeaderBold,
        margins: { top: form.oddHeaderTopMargin },
      },
      even: {
        content: form.evenHeaderContent,
        useCurrentTitle: form.evenUseCurrentTitle,
        position: form.evenHeaderPosition,
        font: form.evenHeaderFont,
        size: form.evenHeaderSize,
        align: form.evenHeaderAlign,
        bold: form.evenHeaderBold,
        margins: { top: form.evenHeaderTopMargin, bottom: form.headerBottomMargin ?? 1.5 },
      },
      border: {
        style: form.headerBorderStyle,
        color: form.headerBorderColor,
        size: str(form.headerBorderSize),
        space: str(form.headerBorderSpace),
        shadow: form.headerBorderShadow,
      },
    },
    footer: {
      enabled: boolToStr(form.enableFooter),
      font: form.footerFont,
      size: form.footerSize,
      type: form.pageNumberType,
      align: form.pageNumberAlign,
      prefix: form.pageNumberPrefix,
      suffix: form.pageNumberSuffix,
      showTotalPages: boolToStr(form.showTotalPages),
      differentPages: boolToStr(form.enableDifferentFooter),
      odd: {
        font: form.footerFont,
        size: form.footerSize,
        type: form.oddPageNumberType,
        prefix: form.oddPageNumberPrefix,
        suffix: form.oddPageNumberSuffix,
        bottom: 1.5,
        align: form.oddPageNumberAlign,
      },
      even: {
        font: form.footerFont,
        size: form.footerSize,
        type: form.evenPageNumberType,
        prefix: form.evenPageNumberPrefix,
        suffix: form.evenPageNumberSuffix,
        align: form.evenPageNumberAlign,
      },
      frontMatter: {
        enabled: boolToStr(form.enableFrontMatterFooter),
        font: form.frontMatterFooterFont,
        size: form.frontMatterFooterSize,
        type: form.frontMatterPageNumberType,
        align: form.frontMatterPageNumberAlign,
      },
    },
    pageMargins: {
      top: form.topMargin,
      bottom: form.bottomMargin,
      left: form.leftMargin,
      right: form.rightMargin,
    },
    cankaowenxian: {
      ckwxxuhaoswitch: boolToStr(form.ckwxXuhaoSwitch),
      titleUseChapter: boolToStr(form.ckwxTitleUseChapter),
      titleSettings: {
        font: form.ckwxTitleFont,
        size: form.ckwxTitleSize,
        align: form.ckwxTitleAlign,
        bold: form.ckwxTitleBold,
        Before: {
          value: str(form.ckwxTitleBefore),
          type: form.ckwxTitleBeforeUnit,
        },
        After: {
          value: str(form.ckwxTitleAfter),
          type: form.ckwxTitleAfterUnit,
        },
      },
      font: form.ckwxFont,
      size: form.ckwxSize,
      align: form.ckwxAlign,
      bold: form.ckwxBold,
      lineSpacing: {
        rule: form.ckwxSpacingRule,
        value: str(form.ckwxSpacingValue),
        unit: form.ckwxSpacingRuleUnit,
      },
      Before: {
        value: str(form.ckwxBefore),
        type: form.ckwxBeforeUnit,
      },
      After: {
        value: str(form.ckwxAfter),
        type: form.ckwxAfterUnit,
      },
      firstLineIndent: str(form.ckwxFirstLineIndent),
      firstLineIndentUnit: form.ckwxIndentUnit,
      leftIndent: str(form.ckwxLeftIndent),
      leftIndentUnit: form.ckwxLeftIndentUnit,
    },
    pageSettings: {
      margins: {
        top: form.topMargin,
        bottom: form.bottomMargin,
        left: form.leftMargin,
        right: form.rightMargin,
        unit: 'cm',
      },
      paperSize,
    },
    otherstting: {
      title1switch: boolToStr(form.title1Switch),
      yinyanswitch: boolToStr(form.yinyanSetting),
      zhixieswitch: boolToStr(form.zhixieSetting),
      zongjieswitch: boolToStr(form.zongjieSetting),
      jiangchong: '0',
    },
  }
}

function saveSettings() {
  // 编辑模式下回填原始模板名称与封面
  if (isEditMode.value) {
    templateName.value = originalTemplateName.value || ''
    templateLogo.value = originalTemplateLogo.value || ''
  } else {
    templateName.value = ''
    templateLogo.value = ''
  }
  saving.value = false
  showSaveModal.value = true
}

function triggerLogoUpload() {
  if (uploadingLogo.value) return
  logoInput.value?.click()
}

async function onLogoChange(e) {
  const file = e.target.files?.[0]
  if (!file) return

  // 校验图片格式
  const ext = file.name.split('.').pop()?.toLowerCase()
  if (!['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
    toast.error('仅支持 jpg/jpeg/png/gif/webp 格式')
    if (logoInput.value) logoInput.value.value = ''
    return
  }
  // 校验大小
  if (file.size > 2 * 1024 * 1024) {
    toast.error('图片大小不能超过 2MB')
    if (logoInput.value) logoInput.value.value = ''
    return
  }

  uploadingLogo.value = true
  try {
    // 1. 获取预签名上传地址
    const sigRes = await api.post('/api/template/uploadLogo', { ext })
    if (!sigRes.ok || !sigRes.data?.put_url) {
      toast.error(sigRes.msg || '获取上传签名失败')
      return
    }
    const sig = sigRes.data

    // 2. 前端直传 OSS
    const putResp = await fetch(sig.put_url, {
      method: 'PUT',
      body: file,
      headers: { 'Content-Type': sig.content_type },
    })
    if (!putResp.ok) {
      toast.error('文件上传失败：HTTP ' + putResp.status)
      return
    }

    // 3. 用下载网关 URL 作为 logo 地址
    templateLogo.value = sig.download_url
    toast.success('封面上传成功，已保存至云端')
  } catch {
    toast.error('封面上传失败')
  } finally {
    uploadingLogo.value = false
    if (logoInput.value) logoInput.value.value = ''
  }
}

async function confirmSave() {
  if (!templateName.value.trim()) {
    toast.error('请输入模板名称')
    return
  }
  saving.value = true
  try {
    const payload = {
      name: templateName.value,
      logo: templateLogo.value,
      conjson: collectSettings(),
    }
    let res
    if (isEditMode.value) {
      // 编辑模式:调用 update 接口
      res = await api.post('/api/template/update', { id: templateId.value, ...payload })
    } else {
      // 新建模式:调用 save 接口
      res = await api.post('/api/template/save', payload)
    }
    if (res.ok) {
      toast.success(res.msg || (isEditMode.value ? '更新成功' : '保存成功'))
      showSaveModal.value = false
      if (isEditMode.value) {
        // 编辑模式下同步更新原始名称,便于下次打开模态框回填
        originalTemplateName.value = templateName.value
        originalTemplateLogo.value = templateLogo.value
      } else {
        templateName.value = ''
        templateLogo.value = ''
      }
    }
  } catch { /* handled by interceptor */ }
  finally { saving.value = false }
}

// ───────── 编辑模式:加载已有模板 ─────────

// 工具函数:容错读取对象属性
const pick = (obj, ...keys) => {
  for (const k of keys) {
    if (obj && typeof obj === 'object' && k in obj && obj[k] !== undefined && obj[k] !== null) {
      return obj[k]
    }
  }
  return undefined
}
// 字符串 "1"/"0" 转 boolean
const strToBool = (v, def = false) => {
  if (v === true || v === '1' || v === 1) return true
  if (v === false || v === '0' || v === 0) return false
  return def
}
// 字符串数字转 number
const toNum = (v, def = 0) => {
  const n = Number(v)
  return Number.isFinite(n) ? n : def
}

// 将后端返回的 conjson 反向回填到 form 中(仅回填存在的字段,缺失字段保持表单默认值)
function applyConjsonToForm(cj) {
  if (!cj || typeof cj !== 'object') return

  // ===== cover =====
  if (cj.cover) {
    const url = pick(cj.cover, 'documentUrl', 'url')
    if (typeof url === 'string') {
      coverOssUrl.value = url
      // 编辑模式下不强制设置 selectedFile,文件输入需要用户重新选择
    }
  }

  // ===== abstract =====
  const ab = cj.abstract
  if (ab) {
    form.enableEnglish = strToBool(pick(ab, 'enableEnglish'), true)
    form.specialSetting = strToBool(pick(ab, 'SpecialSettingSwitch'))

    if (ab.cnAbstract) {
      const c = ab.cnAbstract
      form.cnAbstractFirstLineIndent = toNum(c.firstLineIndent, form.cnAbstractFirstLineIndent)
      if (c.firstLineIndentUnit) form.cnAbstractIndentUnit = c.firstLineIndentUnit
      if (c.font) form.cnAbstractFont = c.font
      if (c.size) form.cnAbstractSize = c.size
      if (c.align) form.cnAbstractAlign = c.align
      if (c.lineSpacing) {
        if (c.lineSpacing.rule) form.cnAbstractLineSpacingRule = c.lineSpacing.rule
        if (c.lineSpacing.value !== undefined) form.cnAbstractLineSpacingValue = toNum(c.lineSpacing.value, form.cnAbstractLineSpacingValue)
        if (c.lineSpacing.unit) form.cnAbstractLineSpacingRuleUnit = c.lineSpacing.unit
      }
      if (c.cnAbstractTitleFont) form.cnAbstractTitleFont = c.cnAbstractTitleFont
      if (c.cnAbstractTitleSize) form.cnAbstractTitleSize = c.cnAbstractTitleSize
      if (c.cnAbstractTitleAlign) form.cnAbstractTitleAlign = c.cnAbstractTitleAlign
      if (c.cnAbstractTitleInterval !== undefined) form.cnAbstractTitleInterval = toNum(c.cnAbstractTitleInterval, form.cnAbstractTitleInterval)
      if (c.cnAbstractTitleBold !== undefined) form.cnAbstractTitleBold = strToBool(c.cnAbstractTitleBold, form.cnAbstractTitleBold)
    }

    if (ab.keywords) {
      const k = ab.keywords
      if (k.AbstractKeywordsNum !== undefined) form.abstractKeywordsNum = toNum(k.AbstractKeywordsNum, form.abstractKeywordsNum)
      if (k.AbstractKeywordsSymbol !== undefined) form.abstractKeywordsSymbol = k.AbstractKeywordsSymbol
      if (k.AbstractKeywordsIntervalNum !== undefined) form.abstractKeywordsIntervalNum = toNum(k.AbstractKeywordsIntervalNum, form.abstractKeywordsIntervalNum)
    }

    if (ab.cnAbstractkeywords) {
      const k = ab.cnAbstractkeywords
      if (k.cnAbstractKeywordsFont) form.cnAbstractKeywordsFont = k.cnAbstractKeywordsFont
      if (k.cnAbstractKeywordsSize) form.cnAbstractKeywordsSize = k.cnAbstractKeywordsSize
      if (k.cnAbstractKeywordsBold !== undefined) form.cnAbstractKeywordsBold = strToBool(k.cnAbstractKeywordsBold, form.cnAbstractKeywordsBold)
      if (k.cnAbstractKeywordsContentFont) form.cnAbstractKeywordsContentFont = k.cnAbstractKeywordsContentFont
      if (k.cnAbstractKeywordsContentSize) form.cnAbstractKeywordsContentSize = k.cnAbstractKeywordsContentSize
    }

    if (ab.enAbstract) {
      const e = ab.enAbstract
      form.enAbstractFirstLineIndent = toNum(e.firstLineIndent, form.enAbstractFirstLineIndent)
      if (e.firstLineIndentUnit) form.enAbstractIndentUnit = e.firstLineIndentUnit
      if (e.font) form.enAbstractFont = e.font
      if (e.size) form.enAbstractSize = e.size
      if (e.align) form.enAbstractAlign = e.align
      if (e.lineSpacing) {
        if (e.lineSpacing.rule) form.enAbstractLineSpacingRule = e.lineSpacing.rule
        if (e.lineSpacing.value !== undefined) form.enAbstractLineSpacingValue = toNum(e.lineSpacing.value, form.enAbstractLineSpacingValue)
        if (e.lineSpacing.unit) form.enAbstractLineSpacingRuleUnit = e.lineSpacing.unit
      }
      if (e.enAbstractTitleFont) form.enAbstractTitleFont = e.enAbstractTitleFont
      if (e.enAbstractTitleSize) form.enAbstractTitleSize = e.enAbstractTitleSize
      if (e.enAbstractTitleAlign) form.enAbstractTitleAlign = e.enAbstractTitleAlign
      if (e.enAbstractTitleInterval !== undefined) form.enAbstractTitleInterval = toNum(e.enAbstractTitleInterval, form.enAbstractTitleInterval)
      if (e.enAbstractTitleBold !== undefined) form.enAbstractTitleBold = strToBool(e.enAbstractTitleBold, form.enAbstractTitleBold)
    }

    if (ab.enAbstractkeywords) {
      const k = ab.enAbstractkeywords
      if (k.enAbstractKeywordsFont) form.enAbstractKeywordsFont = k.enAbstractKeywordsFont
      if (k.enAbstractKeywordsSize) form.enAbstractKeywordsSize = k.enAbstractKeywordsSize
      if (k.enAbstractKeywordsBold !== undefined) form.enAbstractKeywordsBold = strToBool(k.enAbstractKeywordsBold, form.enAbstractKeywordsBold)
      if (k.enAbstractKeywordsContentFont) form.enAbstractKeywordsContentFont = k.enAbstractKeywordsContentFont
      if (k.enAbstractKeywordsContentSize) form.enAbstractKeywordsContentSize = k.enAbstractKeywordsContentSize
    }

    if (ab.SpecialSetting) {
      const s = ab.SpecialSetting
      if (s.AbstractTitletopTitle !== undefined) form.abstractTitleTopTitle = s.AbstractTitletopTitle
      if (s.Special_settingTitleFont) form.specialTitleFont = s.Special_settingTitleFont
      if (s.Special_settingTitleSize) form.specialTitleSize = s.Special_settingTitleSize
      if (s.lineSpacing) {
        if (s.lineSpacing.rule) form.specialTitleSpacingRule = s.lineSpacing.rule
        if (s.lineSpacing.value !== undefined) form.specialTitleSpacingValue = toNum(s.lineSpacing.value, form.specialTitleSpacingValue)
      }
      if (s.Before && s.Before.value !== undefined) {
        form.specialTitleBefore = toNum(s.Before.value, form.specialTitleBefore)
        if (s.Before.type) form.specialTitleBeforeUnit = s.Before.type
      }
      if (s.After && s.After.value !== undefined) {
        form.specialTitleAfter = toNum(s.After.value, form.specialTitleAfter)
        if (s.After.type) form.specialTitleAfterUnit = s.After.type
      }
      if (s.TitleAndContent !== undefined) form.abstractTitleLine = toNum(s.TitleAndContent, form.abstractTitleLine)
      if (s.ContentAndKeywords !== undefined) form.abstractContentLine = toNum(s.ContentAndKeywords, form.abstractContentLine)
    }
  }

  // ===== titles =====
  if (cj.titles) {
    const applyTitle = (key, t) => {
      if (!t) return
      if (t.numberFormat !== undefined) form[`${key}NumberFormat`] = String(t.numberFormat)
      if (t.separator !== undefined) form[`${key}Separator`] = String(t.separator)
      if (t.font) form[`${key}Font`] = t.font
      if (t.size) form[`${key}Size`] = t.size
      if (t.align) form[`${key}Align`] = t.align
      if (t.bold !== undefined) form[`${key}Bold`] = strToBool(t.bold, form[`${key}Bold`])
      if (t.lineSpacing) {
        if (t.lineSpacing.rule) form[`${key}LineSpacingRule`] = t.lineSpacing.rule
        if (t.lineSpacing.value !== undefined) form[`${key}LineSpacingValue`] = toNum(t.lineSpacing.value, form[`${key}LineSpacingValue`])
        if (t.lineSpacing.unit) form[`${key}LineSpacingRuleUnit`] = t.lineSpacing.unit
      }
      if (t.Before && t.Before.value !== undefined) {
        form[`${key}SpaceBefore`] = toNum(t.Before.value, form[`${key}SpaceBefore`])
        if (t.Before.type) form[`${key}SpaceBeforeUnit`] = t.Before.type
      }
      if (t.After && t.After.value !== undefined) {
        form[`${key}SpaceAfter`] = toNum(t.After.value, form[`${key}SpaceAfter`])
        if (t.After.type) form[`${key}SpaceAfterUnit`] = t.After.type
      }
    }
    applyTitle('title1', cj.titles.title1)
    applyTitle('title2', cj.titles.title2)
    applyTitle('title3', cj.titles.title3)
  }

  // ===== content =====
  if (cj.content) {
    const c = cj.content
    if (c.contentNumEnSwitch !== undefined) form.contentNumEnSwitch = strToBool(c.contentNumEnSwitch)
    if (c.contentNumEnFont) form.contentNumEnFont = c.contentNumEnFont
    if (c.contentNumEnSize) form.contentNumEnSize = c.contentNumEnSize
    if (c.font) form.contentFont = c.font
    if (c.size) form.contentSize = c.size
    if (c.paragraph) {
      const p = c.paragraph
      if (p.align) form.paragraphAlign = p.align
      if (p.firstLineIndent !== undefined) form.firstLineIndent = toNum(p.firstLineIndent, form.firstLineIndent)
      if (p.firstLineIndentUnit) form.indentUnit = p.firstLineIndentUnit
      if (p.leftIndent !== undefined) form.leftIndent = toNum(p.leftIndent, form.leftIndent)
      if (p.leftIndentUnit) form.leftIndentUnit = p.leftIndentUnit
      if (p.rightIndent !== undefined) form.rightIndent = toNum(p.rightIndent, form.rightIndent)
      if (p.rightIndentUnit) form.rightIndentUnit = p.rightIndentUnit
      if (p.lineSpacing) {
        if (p.lineSpacing.rule) form.lineSpacingRule = p.lineSpacing.rule
        if (p.lineSpacing.value !== undefined) form.lineSpacingValue = toNum(p.lineSpacing.value, form.lineSpacingValue)
        if (p.lineSpacing.unit) form.lineSpacingRuleUnit = p.lineSpacing.unit
      }
      if (p.Before && p.Before.value !== undefined) {
        form.spaceBefore = toNum(p.Before.value, form.spaceBefore)
        if (p.Before.type) form.spaceBeforeUnit = p.Before.type
      }
      if (p.After && p.After.value !== undefined) {
        form.spaceAfter = toNum(p.After.value, form.spaceAfter)
        if (p.After.type) form.spaceAfterUnit = p.After.type
      }
    }
  }

  // ===== toc =====
  if (cj.toc) {
    const t = cj.toc
    if (t.switchThird !== undefined) form.switchThird = strToBool(t.switchThird, true)
    if (t.showTitle !== undefined) form.showTocTitle = strToBool(t.showTitle, true)
    if (t.titleSettings) {
      const ts = t.titleSettings
      if (ts.font) form.tocTitleFont = ts.font
      if (ts.size) form.tocTitleSize = ts.size
      if (ts.align) form.tocTitleAlign = ts.align
      if (ts.lineSpacing && ts.lineSpacing.rule) form.tocTitleLineSpacingRule = ts.lineSpacing.rule
      if (ts.lineSpacing && ts.lineSpacing.value !== undefined) form.tocTitleLineSpacingValue = toNum(ts.lineSpacing.value, form.tocTitleLineSpacingValue)
      if (ts.Before && ts.Before.value !== undefined) form.tocTitleSpaceBefore = toNum(ts.Before.value, form.tocTitleSpaceBefore)
      if (ts.After && ts.After.value !== undefined) form.tocTitleSpaceAfter = toNum(ts.After.value, form.tocTitleSpaceAfter)
    }
    if (t.tocContent) {
      const tc = t.tocContent
      if (tc.font) form.tocFont = tc.font
      if (tc.size) form.tocSize = tc.size
      if (tc.lineSpacing) {
        if (tc.lineSpacing.rule) form.tocLineSpacingRule = tc.lineSpacing.rule
        if (tc.lineSpacing.value !== undefined) form.tocLineSpacingValue = toNum(tc.lineSpacing.value, form.tocLineSpacingValue)
        if (tc.lineSpacing.unit) form.tocLineSpacingRuleUnit = tc.lineSpacing.unit
      }
      if (tc.leader) form.tocLeader = tc.leader
    }
  }

  // ===== header =====
  if (cj.header) {
    const h = cj.header
    if (h.enabled !== undefined) form.enableHeader = strToBool(h.enabled)
    if (h.differentPages !== undefined) form.enableDifferentHeader = strToBool(h.differentPages)
    if (h.single) {
      const s = h.single
      if (s.content !== undefined) form.headerContent = s.content
      if (s.useCurrentTitle !== undefined) form.useCurrentTitle = strToBool(s.useCurrentTitle)
      if (s.position) form.headerPosition = s.position
      if (s.font) form.headerFont = s.font
      if (s.size) form.headerSize = s.size
      if (s.align) form.headerAlign = s.align
      if (s.bold !== undefined) form.headerBold = strToBool(s.bold, form.headerBold)
      if (s.margins && s.margins.top !== undefined) form.headerTopMargin = toNum(s.margins.top, form.headerTopMargin)
    }
    if (h.odd) {
      const o = h.odd
      if (o.content !== undefined) form.oddHeaderContent = o.content
      if (o.useCurrentTitle !== undefined) form.oddUseCurrentTitle = strToBool(o.useCurrentTitle)
      if (o.position) form.oddHeaderPosition = o.position
      if (o.font) form.oddHeaderFont = o.font
      if (o.size) form.oddHeaderSize = o.size
      if (o.align) form.oddHeaderAlign = o.align
      if (o.bold !== undefined) form.oddHeaderBold = strToBool(o.bold, form.oddHeaderBold)
      if (o.margins && o.margins.top !== undefined) form.oddHeaderTopMargin = toNum(o.margins.top, form.oddHeaderTopMargin)
    }
    if (h.even) {
      const e = h.even
      if (e.content !== undefined) form.evenHeaderContent = e.content
      if (e.useCurrentTitle !== undefined) form.evenUseCurrentTitle = strToBool(e.useCurrentTitle)
      if (e.position) form.evenHeaderPosition = e.position
      if (e.font) form.evenHeaderFont = e.font
      if (e.size) form.evenHeaderSize = e.size
      if (e.align) form.evenHeaderAlign = e.align
      if (e.bold !== undefined) form.evenHeaderBold = strToBool(e.bold, form.evenHeaderBold)
      if (e.margins && e.margins.top !== undefined) form.evenHeaderTopMargin = toNum(e.margins.top, form.evenHeaderTopMargin)
    }
    if (h.border) {
      const b = h.border
      if (b.style) form.headerBorderStyle = b.style
      if (b.color) form.headerBorderColor = b.color
      if (b.size !== undefined) form.headerBorderSize = String(b.size)
      if (b.space !== undefined) form.headerBorderSpace = String(b.space)
      if (b.shadow !== undefined) form.headerBorderShadow = strToBool(b.shadow, form.headerBorderShadow)
    }
  }

  // ===== footer =====
  if (cj.footer) {
    const f = cj.footer
    if (f.enabled !== undefined) form.enableFooter = strToBool(f.enabled)
    if (f.font) form.footerFont = f.font
    if (f.size) form.footerSize = f.size
    if (f.type) form.pageNumberType = f.type
    if (f.align) form.pageNumberAlign = f.align
    if (f.prefix !== undefined) form.pageNumberPrefix = f.prefix
    if (f.suffix !== undefined) form.pageNumberSuffix = f.suffix
    if (f.showTotalPages !== undefined) form.showTotalPages = strToBool(f.showTotalPages)
    if (f.differentPages !== undefined) form.enableDifferentFooter = strToBool(f.differentPages)
    if (f.odd) {
      if (f.odd.type) form.oddPageNumberType = f.odd.type
      if (f.odd.prefix !== undefined) form.oddPageNumberPrefix = f.odd.prefix
      if (f.odd.suffix !== undefined) form.oddPageNumberSuffix = f.odd.suffix
      if (f.odd.align) form.oddPageNumberAlign = f.odd.align
    }
    if (f.even) {
      if (f.even.type) form.evenPageNumberType = f.even.type
      if (f.even.prefix !== undefined) form.evenPageNumberPrefix = f.even.prefix
      if (f.even.suffix !== undefined) form.evenPageNumberSuffix = f.even.suffix
      if (f.even.align) form.evenPageNumberAlign = f.even.align
    }
    if (f.frontMatter) {
      const fm = f.frontMatter
      if (fm.enabled !== undefined) form.enableFrontMatterFooter = strToBool(fm.enabled, true)
      if (fm.font) form.frontMatterFooterFont = fm.font
      if (fm.size) form.frontMatterFooterSize = fm.size
      if (fm.type) form.frontMatterPageNumberType = fm.type
      if (fm.align) form.frontMatterPageNumberAlign = fm.align
    }
  }

  // ===== pageMargins / pageSettings =====
  if (cj.pageSettings) {
    const ps = cj.pageSettings
    if (ps.margins) {
      const m = ps.margins
      if (m.top !== undefined) form.topMargin = toNum(m.top, form.topMargin)
      if (m.bottom !== undefined) form.bottomMargin = toNum(m.bottom, form.bottomMargin)
      if (m.left !== undefined) form.leftMargin = toNum(m.left, form.leftMargin)
      if (m.right !== undefined) form.rightMargin = toNum(m.right, form.rightMargin)
    }
    if (ps.paperSize) {
      const p = ps.paperSize
      if (p.size) form.paperSize = p.size
      if (p.orientation) form.paperOrientation = p.orientation
      if (p.size === 'custom') {
        if (p.width !== undefined) form.customPaperWidth = toNum(p.width, form.customPaperWidth)
        if (p.height !== undefined) form.customPaperHeight = toNum(p.height, form.customPaperHeight)
      }
    }
  } else if (cj.pageMargins) {
    const m = cj.pageMargins
    if (m.top !== undefined) form.topMargin = toNum(m.top, form.topMargin)
    if (m.bottom !== undefined) form.bottomMargin = toNum(m.bottom, form.bottomMargin)
    if (m.left !== undefined) form.leftMargin = toNum(m.left, form.leftMargin)
    if (m.right !== undefined) form.rightMargin = toNum(m.right, form.rightMargin)
  }

  // ===== cankaowenxian =====
  if (cj.cankaowenxian) {
    const ck = cj.cankaowenxian
    if (ck.ckwxxuhaoswitch !== undefined) form.ckwxXuhaoSwitch = strToBool(ck.ckwxxuhaoswitch)
    if (ck.titleUseChapter !== undefined) form.ckwxTitleUseChapter = strToBool(ck.titleUseChapter, true)
    if (ck.titleSettings) {
      const ts = ck.titleSettings
      if (ts.font) form.ckwxTitleFont = ts.font
      if (ts.size) form.ckwxTitleSize = ts.size
      if (ts.align) form.ckwxTitleAlign = ts.align
      if (ts.bold !== undefined) form.ckwxTitleBold = strToBool(ts.bold, form.ckwxTitleBold)
      if (ts.Before && ts.Before.value !== undefined) {
        form.ckwxTitleBefore = toNum(ts.Before.value, form.ckwxTitleBefore)
        if (ts.Before.type) form.ckwxTitleBeforeUnit = ts.Before.type
      }
      if (ts.After && ts.After.value !== undefined) {
        form.ckwxTitleAfter = toNum(ts.After.value, form.ckwxTitleAfter)
        if (ts.After.type) form.ckwxTitleAfterUnit = ts.After.type
      }
    }
    if (ck.font) form.ckwxFont = ck.font
    if (ck.size) form.ckwxSize = ck.size
    if (ck.align) form.ckwxAlign = ck.align
    if (ck.bold !== undefined) form.ckwxBold = strToBool(ck.bold, form.ckwxBold)
    if (ck.lineSpacing) {
      if (ck.lineSpacing.rule) form.ckwxSpacingRule = ck.lineSpacing.rule
      if (ck.lineSpacing.value !== undefined) form.ckwxSpacingValue = toNum(ck.lineSpacing.value, form.ckwxSpacingValue)
      if (ck.lineSpacing.unit) form.ckwxSpacingRuleUnit = ck.lineSpacing.unit
    }
    if (ck.Before && ck.Before.value !== undefined) {
      form.ckwxBefore = toNum(ck.Before.value, form.ckwxBefore)
      if (ck.Before.type) form.ckwxBeforeUnit = ck.Before.type
    }
    if (ck.After && ck.After.value !== undefined) {
      form.ckwxAfter = toNum(ck.After.value, form.ckwxAfter)
      if (ck.After.type) form.ckwxAfterUnit = ck.After.type
    }
    if (ck.firstLineIndent !== undefined) form.ckwxFirstLineIndent = toNum(ck.firstLineIndent, form.ckwxFirstLineIndent)
    if (ck.firstLineIndentUnit) form.ckwxIndentUnit = ck.firstLineIndentUnit
    if (ck.leftIndent !== undefined) form.ckwxLeftIndent = toNum(ck.leftIndent, form.ckwxLeftIndent)
    if (ck.leftIndentUnit) form.ckwxLeftIndentUnit = ck.leftIndentUnit
  }

  // ===== otherstting =====
  if (cj.otherstting) {
    const o = cj.otherstting
    if (o.title1switch !== undefined) form.title1Switch = strToBool(o.title1switch, true)
    if (o.yinyanswitch !== undefined) form.yinyanSetting = strToBool(o.yinyanswitch)
    if (o.zhixieswitch !== undefined) form.zhixieSetting = strToBool(o.zhixieswitch)
    if (o.zongjieswitch !== undefined) form.zongjieSetting = strToBool(o.zongjieswitch)
  }
}

async function loadTemplate(id) {
  if (!id) return
  loadingTemplate.value = true
  try {
    const res = await api.get('/api/template/detail', { id })
    if (res.ok && res.data) {
      const data = res.data
      // 回填模板元信息
      templateId.value = Number(data.id) || 0
      originalTemplateName.value = data.name || ''
      originalTemplateLogo.value = data.avt || ''
      // 回填 conjson
      let cj = data.conjson
      if (typeof cj === 'string') {
        try { cj = JSON.parse(cj) } catch { cj = null }
      }
      if (cj && typeof cj === 'object') {
        applyConjsonToForm(cj)
      }
    } else {
      toast.error(res.msg || '加载模板失败')
      router.replace('/pc/template')
    }
  } catch (e) {
    toast.error('加载模板失败')
    router.replace('/pc/template')
  } finally {
    loadingTemplate.value = false
  }
}

// 挂载时检查路由参数,进入编辑模式
onMounted(() => {
  const id = Number(route.query.id) || 0
  if (id > 0) {
    // 先同步设置,避免横幅先显示创建模式再切换为编辑模式
    templateId.value = id
    loadTemplate(id)
  }
})

async function demonstrate() {
  demonstrating.value = true
  demoProgress.value = 0
  demoStatusText.value = '正在解析模板参数...'
  // 进度动画:先快速到 85%,完成后跳 100%
  _demoProgressTimer = setInterval(() => {
    if (demoProgress.value < 85) {
      demoProgress.value = Math.min(85, demoProgress.value + Math.random() * 8 + 2)
    }
  }, 400)
  // 状态文案轮播
  const statuses = [
    '正在解析模板参数...',
    '正在生成文档内容...',
    '正在应用排版规则...',
    '正在渲染页面格式...',
    '正在上传文档...',
  ]
  let si = 0
  _demoTimer = setInterval(() => {
    si = (si + 1) % statuses.length
    demoStatusText.value = statuses[si]
  }, 2500)

  let success = false
  try {
    const settings = collectSettings()
    const res = await api.post('/api/template/demonstrate', {
      jsondata: settings,
    })
    if (res.ok && res.data?.doc_url) {
      previewUrl.value = res.data.doc_url
      clearInterval(_demoProgressTimer)
      demoProgress.value = 100
      demoStatusText.value = '排版完成!'
      // 等 500ms 让用户看到 100%,再关闭动画、弹出结果
      await new Promise(r => setTimeout(r, 500))
      success = true
    } else if (res.msg) {
      toast.error(res.msg)
    }
  } catch (e) {
    toast.error('排版预览请求失败')
  } finally {
    clearInterval(_demoTimer)
    clearInterval(_demoProgressTimer)
    demonstrating.value = false
    if (success) showPreviewModal.value = true
  }
}

function triggerFileUpload() {
  fileInput.value?.click()
}

async function handleFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  await validateAndProcessFile(file)
}

async function handleFileDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (!file) return
  await validateAndProcessFile(file)
}

async function validateAndProcessFile(file) {
  if (!file.name.endsWith('.docx')) {
    toast.error('仅支持 .docx 格式文件')
    return
  }
  if (file.size > 10 * 1024 * 1024) {
    toast.error('文件大小不能超过 10MB')
    return
  }
  const isValidDocx = await validateDocxFile(file)
  if (!isValidDocx) {
    toast.error('文件格式不正确，请勿将 .doc 文件直接修改后缀为 .docx')
    return
  }
  selectedFile.value = file
  coverOssUrl.value = ''
  toast.success(`文件 "${file.name}" 已选择`)
  uploadingCover.value = true
  try {
    // 1. 获取预签名上传地址
    const sigRes = await api.post('/api/template/uploadCover', { ext: 'docx' })
    if (!sigRes.ok || !sigRes.data?.put_url) {
      toast.error(sigRes.msg || '获取上传签名失败')
      return
    }
    const sig = sigRes.data

    // 2. 前端直传 OSS
    const putResp = await fetch(sig.put_url, {
      method: 'PUT',
      body: file,
      headers: { 'Content-Type': sig.content_type },
    })
    if (!putResp.ok) {
      toast.error('文件上传失败：HTTP ' + putResp.status)
      return
    }

    // 3. 用 OSS 公网 URL 作为封面地址
    coverOssUrl.value = sig.download_url
    toast.success('封面上传成功，已保存至云端')
  } catch {
    toast.error('封面上传失败')
  } finally {
    uploadingCover.value = false
  }
}

async function validateDocxFile(file) {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const arr = new Uint8Array(e.target?.result)
      if (arr.length < 2 || arr[0] !== 0x50 || arr[1] !== 0x4B) {
        resolve(false)
        return
      }
      const text = new TextDecoder().decode(arr.slice(0, Math.min(arr.length, 2000)))
      if (text.includes('[Content_Types].xml') || text.includes('word/')) {
        resolve(true)
      } else {
        resolve(false)
      }
    }
    reader.onerror = () => resolve(false)
    reader.readAsArrayBuffer(file.slice(0, 4096))
  })
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

function removeFile() {
  selectedFile.value = null
  coverOssUrl.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}
</script>

<style scoped>
.autodoc-page { min-height: 100vh; padding: 20px 24px 32px; background: transparent; }
.autodoc-layout { display: flex; gap: 16px; align-items: flex-start; }

/* ===== 模式提示横幅 ===== */
.mode-banner {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 14px 20px;
  margin-bottom: 14px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.mode-banner:hover {
  border-color: #cbd5e1;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}

.mode-banner-head {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 5px;
}

.mode-banner-icon {
  position: relative;
  width: 32px; height: 32px;
  border-radius: 9px;
  color: #fff;
  flex-shrink: 0;
  overflow: hidden;
}
.mode-banner-icon::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0) 60%);
}
.mode-banner-icon svg {
  position: absolute;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%);
  width: 15px; height: 15px;
  display: block;
  z-index: 1;
}
.mode-banner-icon.icon-create {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  box-shadow: 0 2px 6px rgba(20, 184, 166, 0.28);
}
.mode-banner-icon.icon-edit {
  background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);
  box-shadow: 0 2px 6px rgba(249, 115, 22, 0.28);
}

.mode-banner-title {
  font-size: 14px; font-weight: 600; color: #0f172a;
  margin: 0;
  line-height: 1.3;
  flex: 1;
  min-width: 0;
}

.mode-tag {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 3px 9px;
  font-size: 11px; font-weight: 500;
  border-radius: 5px;
  line-height: 1.5;
  flex-shrink: 0;
}
.mode-tag-dot {
  width: 5px; height: 5px; border-radius: 50%;
}
.mode-tag.tag-create {
  color: #0d9488; background: #f0fdfa;
}
.mode-tag.tag-create .mode-tag-dot { background: #14b8a6; }
.mode-tag.tag-edit {
  color: #c2410c; background: #fff7ed;
}
.mode-tag.tag-edit .mode-tag-dot { background: #f97316; }

.mode-banner-desc {
  font-size: 12.5px; color: #64748b;
  margin: 0;
  line-height: 1.55;
  padding-left: 44px; /* 对齐到标题：图标 32 + gap 12 */
}
.mode-banner-name { color: #0f172a; font-weight: 500; }

@media (max-width: 600px) {
  .mode-banner { padding: 12px 16px; }
  .mode-banner-desc { padding-left: 0; padding-top: 4px; }
}

.tab-nav {
  width: 180px; flex-shrink: 0; display: flex; flex-direction: column; gap: 2px;
  padding: 10px 8px; background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
  position: sticky; top: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}
.tab-nav-title {
  font-size: 11px; font-weight: 600; color: #94a3b8; padding: 2px 10px 8px;
  letter-spacing: 0.5px; text-transform: uppercase;
}
.tab-btn {
  display: flex; align-items: center; gap: 10px; padding: 10px 12px;
  border: none; border-radius: 8px; background: transparent; color: #475569;
  font-size: 13px; cursor: pointer; transition: background 0.15s, color 0.15s; text-align: left;
}
.tab-btn:hover { background: #f0fdfa; color: #14b8a6; }
.tab-btn.active {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff; font-weight: 500; box-shadow: 0 1px 8px rgba(20,184,166,0.2);
}
.tab-btn .tab-icon {
  width: 18px; height: 18px; flex-shrink: 0;
  transition: color 0.15s;
}
.tab-btn.active .tab-icon { color: #fff; }

.tab-content { flex: 1; min-width: 0; }

.section-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
  padding: 24px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);
}
.section-head {
  display: flex; align-items: center; gap: 12px; margin-bottom: 18px;
  padding-bottom: 14px; border-bottom: 1px solid #f1f5f9;
}
.section-title { font-size: 17px; font-weight: 600; color: #0f172a; margin: 0; }
.section-badge {
  font-size: 12px; color: #14b8a6; background: #f0fdfa;
  padding: 3px 10px; border-radius: 6px; font-weight: 500;
}

.setting-block {
  margin-bottom: 18px; padding-bottom: 18px; border-bottom: 1px solid #f8fafc;
}
.setting-block:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }

.setting-block-group {
  padding: 16px 18px; background: #f8fafc; border-radius: 10px; border: 1px solid #f1f5f9;
}
.block-group-label {
  font-size: 14px; font-weight: 600; color: #0d9488; margin-bottom: 12px;
  display: flex; align-items: center; gap: 8px;
}
.block-group-label::before {
  content: ''; width: 4px; height: 14px; background: #14b8a6; border-radius: 2px;
}
.block-title {
  font-size: 15px; font-weight: 600; color: #334155; margin-bottom: 10px;
  display: flex; align-items: center; gap: 8px;
}
.block-title .block-title-hint { font-size: 12px; font-weight: 400; color: #94a3b8; }
.block-divider { height: 1px; background: #f1f5f9; margin: 12px 0; }
.even-odd-toggle-bar {
  display: flex; align-items: center; margin: 12px 0 8px; padding: 10px 14px;
  background: #f0fdfa; border-left: 3px solid #14b8a6; border-radius: 8px;
}

.form-row {
  display: grid; grid-template-columns: repeat(12, 1fr); gap: 10px 18px; margin-bottom: 10px;
}
.form-row > label { font-size: 12px; color: #64748b; font-weight: 500; }
.form-row:not([class*="cols-"]) > label { grid-column: span 3; display: flex; align-items: center; }
.form-row:not([class*="cols-"]) > input.ds-input,
.form-row:not([class*="cols-"]) > select.ds-select { grid-column: span 9; max-width: 100%; }
.form-row:not([class*="cols-"]) > .radio-group { grid-column: span 9; }
.form-row.cols-2 > * { grid-column: span 6; }
.form-row.cols-3 > * { grid-column: span 4; }
.form-row.cols-4 > * { grid-column: span 3; }
.form-row.cols-6 > * { grid-column: span 2; }

.form-row-inner { display: flex; align-items: center; gap: 8px; }
.form-row-inner > label { font-size: 13px; color: #64748b; }
.toggle-row { display: flex; flex-wrap: wrap; gap: 14px; }
.form-flex { display: flex; flex-wrap: wrap; gap: 16px 26px; margin-bottom: 14px; align-items: center; }
.form-flex > * { flex: 0 0 auto; min-width: 0; }
.form-flex + .form-flex { margin-top: 12px; }

.ds-input {
  width: 100%; max-width: 220px; padding: 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b;
  background: #fff; outline: none; box-sizing: border-box; transition: border-color 0.15s, box-shadow 0.15s;
}
.ds-input::placeholder { color: #cbd5e1; }
.ds-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }
.ds-input:hover:not(:focus) { border-color: #cbd5e1; }

.ds-select {
  width: 100%; max-width: 100%; padding: 8px 12px;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #1e293b;
  background: #fff; outline: none; cursor: pointer; box-sizing: border-box; transition: border-color 0.15s;
}
.ds-select:focus { border-color: #14b8a6; }
.ds-select:hover:not(:focus) { border-color: #cbd5e1; }

.radio-group { display: flex; gap: 14px; flex-wrap: wrap; }
.radio-group label {
  display: flex; align-items: center; gap: 6px; font-size: 14px; color: #475569; cursor: pointer;
}
.radio-group input[type="radio"] { accent-color: #14b8a6; width: 16px; height: 16px; }

.upload-area {
  border: 2px dashed #cbd5e1; border-radius: 14px; padding: 48px 40px;
  text-align: center; background: #f8fafc; transition: all 0.2s ease; cursor: pointer; margin-top: 8px;
}
.upload-area:hover { border-color: #14b8a6; background: #f0fdfa; }
.upload-area.drag-over { border-color: #14b8a6; background: #f0fdfa; }
.upload-area .upload-content { display: flex; flex-direction: column; align-items: center; gap: 16px; }
.upload-area .upload-icon { width: 48px; height: 48px; color: #94a3b8; transition: color 0.2s ease; }
.upload-area .upload-icon svg { width: 100%; height: 100%; }
.upload-area:hover .upload-icon { color: #14b8a6; }
.upload-area .upload-text { display: flex; flex-direction: column; gap: 8px; }
.upload-area .upload-primary-text { font-size: 15px; color: #374151; font-weight: 500; }
.upload-area .upload-secondary-text { font-size: 13px; color: #94a3b8; }
.upload-area.has-file { border-style: solid; border-color: #e2e8f0; background: #fff; cursor: default; }

.file-selected { display: flex; flex-direction: column; align-items: center; gap: 16px; padding: 16px 24px; }
.file-selected .selected-header {
  display: flex; align-items: center; gap: 8px; color: #0d9488; font-size: 16px; font-weight: 600;
}
.file-selected .selected-header .success-icon { width: 20px; height: 20px; }
.file-selected .file-name-tag {
  background: #f3f4f6; border-radius: 20px; padding: 10px 24px; font-size: 14px;
  color: #374151; font-weight: 500; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.file-selected .file-size-text { font-size: 13px; color: #94a3b8; }
.file-selected .file-status-text { font-size: 13px; margin-top: 4px; }
.file-selected .file-status-text.success { color: #22c55e; }
.file-selected .file-status-text.uploading { color: #14b8a6; }
.file-selected .file-actions { display: flex; gap: 12px; margin-top: 8px; }
.file-selected .btn-replace, .file-selected .btn-delete {
  padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 500;
  cursor: pointer; transition: all 0.15s; border: none;
}
.file-selected .btn-replace {
  background: #fff; color: #374151; border: 1px solid #e2e8f0;
}
.file-selected .btn-replace:hover { background: #fff; border-color: #14b8a6; }
.file-selected .btn-delete { background: rgba(239,68,68,0.06); color: #ef4444; }
.file-selected .btn-delete:hover { background: rgba(239,68,68,0.1); }

.upload-requirements {
  margin-top: 20px; padding: 16px 20px; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9;
}
.upload-requirements .requirement-title {
  display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600;
  color: #374151; margin-bottom: 12px;
}
.upload-requirements .requirement-title svg { width: 18px; height: 18px; color: #14b8a6; }
.upload-requirements .requirement-list { margin: 0; padding-left: 0; list-style: none; }
.upload-requirements .requirement-list li {
  position: relative; padding-left: 16px; font-size: 13px; color: #6b7280; line-height: 1.8;
}
.upload-requirements .requirement-list li::before {
  content: ''; position: absolute; left: 0; top: 10px; width: 6px; height: 6px;
  background: #14b8a6; border-radius: 50%; opacity: 0.3;
}
.upload-requirements .requirement-list li strong { color: #374151; font-weight: 500; }
.upload-requirements .demo-link {
  display: inline-flex; align-items: center; gap: 4px; color: #0d9488;
  text-decoration: none; font-weight: 500;
}
.upload-requirements .demo-link svg { width: 14px; height: 14px; }
.upload-requirements .demo-link:hover { color: #14b8a6; text-decoration: underline; }

.action-bar { display: flex; justify-content: center; gap: 12px; margin-top: 20px; }

.ds-btn {
  padding: 10px 28px; border-radius: 8px; font-size: 14px; font-weight: 500;
  border: none; cursor: pointer; transition: all 0.15s;
}
.ds-btn-primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff;
  box-shadow: 0 1px 12px rgba(20,184,166,0.15);
}
.ds-btn-primary:hover:not(:disabled) { box-shadow: 0 2px 16px rgba(20,184,166,0.25); }
.ds-btn-primary:active:not(:disabled) { transform: scale(0.98); }
.ds-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.ds-btn-secondary {
  background: #fff; color: #0d9488; border: 1px solid #e2e8f0;
}
.ds-btn-secondary:hover { border-color: #14b8a6; background: #f0fdfa; }

.modal-overlay {
  position: fixed; inset: 0; background: rgba(15, 23, 42, 0.35); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal-dialog {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 0;
  width: 440px; max-width: 92vw; box-shadow: 0 8px 32px rgba(0,0,0,0.1); overflow: hidden;
  position: relative;
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
}
.modal-header .modal-title {
  display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 600; color: #1f2937;
}
.modal-header .modal-title .modal-title-icon { width: 22px; height: 22px; color: #14b8a6; }
.modal-header .modal-close {
  width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;
  border: none; border-radius: 8px; background: transparent; color: #94a3b8; cursor: pointer; transition: all 0.15s;
}
.modal-header .modal-close svg { width: 18px; height: 18px; }
.modal-header .modal-close:hover { background: #f3f4f6; color: #374151; }

.modal-body { padding: 20px 24px 0; }
.modal-body .modal-desc { font-size: 13px; color: #6b7280; margin-bottom: 20px; line-height: 1.5; }
.modal-body .modal-field { margin-bottom: 20px; }
.modal-body .modal-field label {
  display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 8px;
}
.modal-body .modal-field label .required { color: #ef4444; margin-left: 2px; }
.modal-body .ds-input {
  width: 100%; max-width: 100%; padding: 10px 14px; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 14px; color: #1f2937; background: #fff; outline: none;
  box-sizing: border-box; transition: all 0.2s;
}
.modal-body .ds-input::placeholder { color: #cbd5e1; }
.modal-body .ds-input:focus { border-color: #14b8a6; box-shadow: 0 0 0 3px rgba(20,184,166,0.08); }

.modal-body .modal-logo-upload {
  width: 100%; min-height: 120px; display: flex; align-items: center; justify-content: center;
  border: 2px dashed #cbd5e1; border-radius: 12px; cursor: pointer; overflow: hidden;
  background: #f8fafc; transition: all 0.2s;
}
.modal-body .modal-logo-upload:hover { border-color: #14b8a6; background: #f0fdfa; }
.modal-body .modal-logo-preview { max-width: 100%; max-height: 200px; object-fit: contain; display: block; }
.modal-body .modal-logo-placeholder { text-align: center; padding: 24px; }
.modal-body .modal-logo-placeholder svg { width: 36px; height: 36px; color: #94a3b8; margin-bottom: 10px; }
.modal-body .modal-logo-placeholder span { display: block; font-size: 14px; color: #374151; font-weight: 500; }
.modal-body .modal-logo-placeholder .modal-logo-hint { font-size: 12px; color: #94a3b8; margin-top: 4px; font-weight: normal; }

.modal-actions {
  display: flex; justify-content: flex-end; gap: 12px; padding: 20px 24px 24px;
}
.modal-actions .ds-btn {
  padding: 10px 24px; border-radius: 8px; font-size: 14px; font-weight: 500;
  border: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 6px;
}
.modal-actions .ds-btn-primary {
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); color: #fff;
  box-shadow: 0 1px 12px rgba(20,184,166,0.15);
}
.modal-actions .ds-btn-primary:hover:not(:disabled) { box-shadow: 0 2px 16px rgba(20,184,166,0.25); }
.modal-actions .ds-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
.modal-actions .ds-btn-secondary {
  background: #fff; color: #6b7280; border: 1px solid #e2e8f0;
}
.modal-actions .ds-btn-secondary:hover { background: #fff; border-color: #14b8a6; color: #374151; }
.modal-actions .btn-loading {
  width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite;
}

/* 排版演示结果 */
/* ── 排版预览结果弹窗 ── */
.preview-modal-overlay { animation: previewFadeIn 0.25s ease-out; }
.preview-dialog {
  width: 420px; max-width: 92vw; border: none; border-radius: 20px; overflow: hidden;
  box-shadow: 0 24px 48px -8px rgba(0,0,0,0.18), 0 0 0 1px rgba(0,0,0,0.04);
  animation: previewScaleIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.preview-accent-bar {
  height: 4px;
  background: linear-gradient(90deg, #14b8a6 0%, #06b6d4 50%, #3b82f6 100%);
}
.preview-close {
  position: absolute; top: 14px; right: 14px; z-index: 2;
  width: 30px; height: 30px; border: none; border-radius: 50%;
  background: rgba(0,0,0,0.04); color: #64748b; cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.preview-close svg { width: 16px; height: 16px; }
.preview-close:hover { background: rgba(0,0,0,0.08); color: #334155; transform: rotate(90deg); }
.preview-body { padding: 36px 32px 32px !important; }
.preview-result { text-align: center; }
.preview-success-icon {
  width: 64px; height: 64px; margin: 0 auto 20px;
  border-radius: 50%; display: flex; align-items: center; justify-content: center;
  background: linear-gradient(135deg, #ccfbf1 0%, #a7f3d0 100%);
  box-shadow: 0 8px 24px -4px rgba(20,184,166,0.3), inset 0 1px 2px rgba(255,255,255,0.5);
  animation: successBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.1s both;
}
.preview-success-icon svg { width: 32px; height: 32px; color: #0d9488; }
.preview-message {
  font-size: 19px; font-weight: 700; color: #0f172a; margin-bottom: 10px;
  letter-spacing: 0.5px;
}
.preview-desc {
  font-size: 13px; color: #64748b; margin-bottom: 28px; line-height: 1.7;
}
.preview-actions { display: flex; gap: 10px; justify-content: center; }
.preview-download-btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 10px 22px; border: none; border-radius: 10px;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: #fff; font-size: 14px; font-weight: 600; text-decoration: none;
  cursor: pointer; transition: all 0.25s;
  box-shadow: 0 4px 14px -2px rgba(20,184,166,0.4);
}
.preview-download-btn svg { width: 16px; height: 16px; }
.preview-download-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px -2px rgba(20,184,166,0.5);
}
.preview-close-btn {
  padding: 10px 20px; border: 1px solid #e2e8f0; border-radius: 10px;
  background: #fff; color: #475569; font-size: 14px; font-weight: 500; cursor: pointer;
  transition: all 0.2s;
}
.preview-close-btn:hover { background: #f8fafc; border-color: #cbd5e1; }

@keyframes previewFadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes previewScaleIn {
  from { opacity: 0; transform: scale(0.9) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes successBounce {
  0% { transform: scale(0); }
  60% { transform: scale(1.15); }
  100% { transform: scale(1); }
}

.loading-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.45); backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center; z-index: 2000;
}
.loading-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 48px 56px;
  text-align: center; box-shadow: 0 8px 40px rgba(0,0,0,0.12); max-width: 400px; width: 90vw;
}
.loading-spinner { position: relative; width: 80px; height: 80px; margin: 0 auto 24px; }
.loading-spinner .loading-ring { width: 80px; height: 80px; animation: spin 1.2s linear infinite; }
.loading-spinner .loading-ring circle { stroke: #14b8a6; stroke-dasharray: 100; stroke-dashoffset: 70; }
.loading-spinner .loading-icon {
  position: absolute; inset: 0; margin: auto; width: 30px; height: 30px; color: #14b8a6;
}
.loading-title { font-size: 17px; font-weight: 600; color: #1f2937; margin-bottom: 8px; }
.loading-sub { font-size: 13px; color: #94a3b8; margin-bottom: 28px; }
.loading-bar-track { height: 4px; background: #f1f5f9; border-radius: 4px; overflow: hidden; }
.loading-bar-fill {
  height: 100%; width: 30%; background: linear-gradient(90deg, #14b8a6, #0d9488, #14b8a6);
  background-size: 200% 100%; border-radius: 4px; animation: loading-bar 1.8s ease-in-out infinite;
}

.result-info { padding: 4px 0 8px; }
.result-info .result-info-item {
  display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc;
}
.result-info .result-info-item:last-child { border-bottom: none; }
.result-info .result-info-label { font-size: 13px; color: #94a3b8; width: 80px; flex-shrink: 0; }
.result-info .result-info-value { font-size: 14px; color: #1f2937; font-weight: 500; word-break: break-all; }

.result-actions { border-top: 1px solid #f8fafc; }
.result-actions .ds-btn { display: inline-flex; align-items: center; gap: 6px; }
.btn-icon { width: 16px; height: 16px; }
.result-dialog { width: 460px; }

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes loading-bar {
  0% { transform: translateX(-100%); }
  50% { transform: translateX(100%); }
  100% { transform: translateX(233%); }
}

/* ── 排版演示加载动画 ── */
.demo-fade-enter-active, .demo-fade-leave-active { transition: opacity 0.3s ease; }
.demo-fade-enter-from, .demo-fade-leave-to { opacity: 0; }

.demo-loading-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.55); backdrop-filter: blur(8px);
  display: flex; align-items: center; justify-content: center; z-index: 2000;
}
.demo-loading-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 40px 48px 36px;
  text-align: center; box-shadow: 0 12px 48px rgba(0,0,0,0.15);
  max-width: 380px; width: 90vw;
}
.demo-doc-animation {
  position: relative; width: 80px; height: 100px; margin: 0 auto 24px;
}
.demo-doc-svg {
  width: 80px; height: 100px;
  filter: drop-shadow(0 4px 12px rgba(20,184,166,0.15));
  animation: doc-float 2.5s ease-in-out infinite;
}
.demo-doc-page {
  animation: doc-page-glow 2.5s ease-in-out infinite;
}
.demo-doc-line {
  transform-origin: left center;
  animation: doc-line-draw 2.5s ease-in-out infinite;
}
.demo-doc-line-1 { animation-delay: 0s; }
.demo-doc-line-2 { animation-delay: 0.15s; }
.demo-doc-line-3 { animation-delay: 0.3s; }
.demo-doc-line-4 { animation-delay: 0.45s; }
.demo-doc-line-5 { animation-delay: 0.6s; }
.demo-doc-line-6 { animation-delay: 0.75s; }
.demo-doc-line-7 { animation-delay: 0.9s; }

.demo-pen-animation {
  position: absolute; right: -8px; top: 10px;
  width: 22px; height: 22px; color: #14b8a6;
  animation: pen-write 2.5s ease-in-out infinite;
}
.demo-pen-animation svg { width: 100%; height: 100%; }

.demo-ring-pulse {
  position: absolute; inset: -8px; border-radius: 50%;
  border: 2px solid rgba(20,184,166,0.2);
  animation: ring-pulse 2s ease-out infinite;
}

.demo-loading-title {
  font-size: 17px; font-weight: 600; color: #1f2937; margin-bottom: 6px;
}
.demo-loading-status {
  font-size: 13px; color: #6b7280; margin-bottom: 20px; min-height: 18px;
  transition: opacity 0.3s;
}
.demo-progress-track {
  height: 6px; background: #f1f5f9; border-radius: 6px; overflow: hidden;
  margin-bottom: 8px;
}
.demo-progress-fill {
  height: 100%; border-radius: 6px;
  background: linear-gradient(90deg, #14b8a6 0%, #0d9488 50%, #14b8a6 100%);
  background-size: 200% 100%;
  transition: width 0.4s ease-out;
  animation: progress-shimmer 1.5s linear infinite;
}
.demo-progress-percent {
  font-size: 12px; color: #94a3b8; font-variant-numeric: tabular-nums;
}

@keyframes doc-float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-6px); }
}
@keyframes doc-page-glow {
  0%, 100% { stroke-opacity: 1; }
  50% { stroke-opacity: 0.5; }
}
@keyframes doc-line-draw {
  0%, 100% { transform: scaleX(1); opacity: 1; }
  50% { transform: scaleX(0.3); opacity: 0.3; }
}
@keyframes pen-write {
  0% { transform: translate(0, 0) rotate(-15deg); }
  25% { transform: translate(-12px, 8px) rotate(-25deg); }
  50% { transform: translate(-20px, 20px) rotate(-15deg); }
  75% { transform: translate(-10px, 35px) rotate(-25deg); }
  100% { transform: translate(0, 0) rotate(-15deg); }
}
@keyframes ring-pulse {
  0% { transform: scale(0.8); opacity: 0.8; }
  100% { transform: scale(1.4); opacity: 0; }
}
@keyframes progress-shimmer {
  0% { background-position: 0% 0%; }
  100% { background-position: 200% 0%; }
}

@media (max-width: 960px) {
  .autodoc-page { padding: 12px 8px 24px; }
  .autodoc-layout { flex-direction: column; }
  .tab-nav {
    flex-direction: row; flex-wrap: wrap; width: 100%; position: static;
    padding: 4px; gap: 2px; overflow-x: auto;
  }
  .tab-nav .tab-nav-title { display: none; }
  .tab-nav .tab-btn { padding: 6px 10px; font-size: 11px; border-radius: 6px; }
  .tab-nav .tab-btn .tab-icon { width: 15px; height: 15px; }
  .section-card { padding: 18px 14px; }
  .form-row { grid-template-columns: 1fr; }
  .form-row.cols-2 > *, .form-row.cols-3 > *, .form-row.cols-4 > * { grid-column: auto; }
}

/* ===== 顶部范文导入入口 ===== */
.create-entry { display: flex; justify-content: flex-end; margin-bottom: 14px; }
.create-entry-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 9px 16px; border: 1px solid #e2e8f0; border-radius: 10px;
  background: #fff; color: #475569; font-size: 13px; font-weight: 600;
  cursor: pointer; transition: all 0.18s; box-shadow: 0 1px 3px rgba(15,23,42,0.04);
}
.create-entry-btn svg { width: 16px; height: 16px; color: #0d9488; }
.create-entry-btn:hover { border-color: #0d9488; color: #0d9488; background: #f0fdfa; }
.create-entry-tag {
  font-size: 10px; line-height: 1; padding: 3px 6px; border-radius: 5px;
  color: #fff; background: #f97316; font-weight: 600;
}
</style>
